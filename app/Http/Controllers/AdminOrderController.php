<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\PrescriptionItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sale;
use App\Services\NotificationService;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ProductBatch;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AdminOrderController extends Controller
{
    public function index()
    {
        $prescriptions = Prescription::with('order')
            ->whereIn('status', ['pending', 'approved', 'partially_approved', 'cancelled'])
            ->whereNotIn('status', ['completed'])
            ->latest()
            ->get();

        $products = Product::whereHas('batches', function ($q) {
            $q->where('quantity_remaining', '>', 0);
        })->get();

        return view('orders.orders', compact('prescriptions', 'products'));
    }
    

    public function getSaleDetails($saleId)
    {

        try {
            $sale = Sale::with(['customer', 'prescription'])
                ->where('id', $saleId)
                ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found'
                ], 404);
            }

            // Get sale items from the sale_items table
            $items = SaleItem::with('product')
                ->where('sale_id', $sale->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'product_name' => $item->product->product_name ?? 'Unknown Product',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal
                    ];
                });

            $saleDetails = [
                'id' => $sale->id,
                'order_id' => $sale->order_id ?? 'N/A',
                'customer' => [
                    'name' => $sale->customer->name ?? 'Guest',
                    'email' => $sale->customer->email ?? 'No email',
                    'phone' => $sale->customer->phone ?? 'No phone'
                ],
                'prescription' => [
                    'id' => $sale->prescription_id,
                    'file_path' => $sale->prescription->file_path ?? null,
                    'notes' => $sale->prescription->notes ?? null
                ],
                'items' => $items,
                'total_amount' => $sale->total_amount,
                'total_items' => $sale->total_items,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'sale_date' => $sale->sale_date->format('M d, Y h:i A'),
                'notes' => $sale->notes
            ];

            return response()->json([
                'success' => true,
                'sale' => $saleDetails
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting sale details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function completeOrder(Request $request, $prescriptionId)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'nullable|string|in:cash,card,gcash,online',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        try {
            DB::beginTransaction();

            $prescription = Prescription::find($prescriptionId);
            if (!$prescription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prescription not found'
                ], 404);
            }

            $prescriptionItems = PrescriptionItem::where('prescription_id', $prescriptionId)
                ->with(['product' => function ($query) {
                    $query->select('id', 'product_name');
                }])
                ->get();

            if ($prescriptionItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products selected for this prescription.'
                ], 400);
            }

            $customerId = $prescription->customer_id ?? $prescription->user_id ?? 1;

            $order = $prescription->order;
            if (!$order) {
                $order = Order::create([
                    'prescription_id' => $prescriptionId,
                    'customer_id' => $customerId,
                    'status' => 'pending',
                    'order_date' => now(),
                    'order_id' => 'ORD-' . now()->format('YmdHis') . '-' . $prescriptionId,
                ]);
                $prescription->update(['order_id' => $order->id]);
            }

            $order->orderItems()->delete();

            $totalAmount = 0;
            $totalItems = 0;

            foreach ($prescriptionItems as $prescItem) {
                $product = $prescItem->product ?? Product::find($prescItem->product_id);
                if (!$product) {
                    throw new \Exception("Product not found for prescription item {$prescItem->id}");
                }

                $latestBatch = ProductBatch::where('product_id', $product->id)
                    ->where('quantity_remaining', '>', 0)
                    ->whereNotNull('sale_price')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $salePrice = $latestBatch ? (float)$latestBatch->sale_price : 0;

                if ($salePrice <= 0) {
                    throw new \Exception("Invalid sale price for product {$product->product_name}");
                }

                $subtotal = $prescItem->quantity * $salePrice;
                $totalAmount += $subtotal;
                $totalItems += $prescItem->quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $prescItem->product_id,
                    'quantity' => $prescItem->quantity,
                    'available' => 1,
                ]);
            }

            $stockErrors = [];
            foreach ($prescriptionItems as $item) {
                $totalBatchStock = ProductBatch::where('product_id', $item->product_id)
                    ->sum('quantity_remaining');

                if ($totalBatchStock < $item->quantity) {
                    $stockErrors[] = "Insufficient stock for {$item->product->product_name}. Available: {$totalBatchStock}, Required: {$item->quantity}";
                }
            }

            if (!empty($stockErrors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => implode('. ', $stockErrors)
                ], 400);
            }

            if ($totalAmount <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order total is zero. Please check product prices.'
                ], 400);
            }

            $sale = Sale::create([
                'prescription_id' => $prescriptionId,
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'total_items' => $totalItems,
                'payment_method' => $request->input('payment_method', 'cash'),
                'notes' => $request->input('notes'),
                'sale_date' => now(),
                'status' => 'completed'
            ]);

            foreach ($prescriptionItems as $prescItem) {
                $product = $prescItem->product ?? Product::find($prescItem->product_id);

                $latestBatch = ProductBatch::where('product_id', $product->id)
                    ->where('quantity_remaining', '>', 0)
                    ->latest('created_at')
                    ->first();

                $itemPrice = $latestBatch ? (float)$latestBatch->sale_price : 0;
                $itemSubtotal = $prescItem->quantity * $itemPrice;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $prescItem->product_id,
                    'quantity' => $prescItem->quantity,
                    'unit_price' => $itemPrice,
                    'subtotal' => $itemSubtotal
                ]);

                $qtyToDeduct = $prescItem->quantity;
                $batches = ProductBatch::where('product_id', $prescItem->product_id)
                    ->where('quantity_remaining', '>', 0)
                    ->orderBy('expiration_date', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if ($qtyToDeduct <= 0) break;

                    $deduct = min($batch->quantity_remaining, $qtyToDeduct);
                    $batch->quantity_remaining -= $deduct;
                    $batch->save();

                    $qtyToDeduct -= $deduct;

                    StockMovement::create([
                        'product_id' => $prescItem->product_id,
                        'type' => 'sale',
                        'quantity' => -$deduct,
                        'reference_id' => $sale->id,
                        'reference_type' => 'sale',
                        'notes' => "Sale for prescription #{$prescriptionId}, batch {$batch->id}"
                    ]);
                }
            }

            $order->update(['status' => 'completed', 'completed_at' => now()]);
            $prescription->update(['status' => 'completed', 'completed_at' => now()]);

            if (class_exists('App\Services\NotificationService')) {
                NotificationService::notifyOrderCompleted($sale);
                NotificationService::notifyHighValueSale($sale, 3000);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order completed successfully',
                'sale_id' => $sale->id,
                'total_amount' => $totalAmount,
                'total_items' => $totalItems,
                'payment_method' => $sale->payment_method
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error completing order: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete order: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'custom_message' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $prescription = Prescription::with('order')->findOrFail($id);

            // Create order if it doesn't exist
            if (!$prescription->order) {
                $order = new Order([
                    'customer_id' => $prescription->customer_id,
                    'status' => 'approved', // This matches orders table enum
                    'order_id' => 'ORD-' . $prescription->id,
                    'prescription_id' => $prescription->id,
                ]);
                $order->save();
            } else {
                $prescription->order->update(['status' => 'approved']);
            }

            $adminMessage = $request->message;
            if ($request->custom_message) {
                $adminMessage .= "\n\nAdditional notes: " . $request->custom_message;
            }

            $prescription->update([
                'status' => 'approved', // This matches prescriptions table enum
                'admin_message' => $adminMessage,
                'updated_at' => now()
            ]);

            NotificationService::notifyOrderApproved($prescription);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order approved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error approving order: ' . $e->getMessage(), [
                'prescription_id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'custom_message' => 'nullable|string|max:500',
        ]);
        try {
            DB::beginTransaction();

            $prescription = Prescription::with('order')->findOrFail($id);

            if (!$prescription->order) {
                $order = new Order([
                    'customer_id' => $prescription->customer_id,
                    'status' => 'cancelled', // orders table uses 'cancelled'
                    'order_id' => 'ORD-' . $prescription->id,
                    'prescription_id' => $prescription->id,
                ]);
                $order->save();
            } else {
                $prescription->order->update(['status' => 'cancelled']);
            }

            $adminMessage = $request->message;
            if ($request->custom_message) {
                $adminMessage .= "\n\nAdditional notes: " . $request->custom_message;
            }

            $prescription->update([
                'status' => 'cancelled',
                'admin_message' => $adminMessage,
                'updated_at' => now()
            ]);

            NotificationService::notifyCustomerOrderCancelled($prescription);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error cancelling order: ' . $e->getMessage(), [
                'prescription_id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }
    public function storePrescriptionItems(Request $request, $prescriptionId)
    {
        try {
            Log::info('Storing prescription items with batch_id', [
                'prescription_id' => $prescriptionId,
                'request_data' => $request->all()
            ]);

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ], 422);
            }

            $prescription = Prescription::findOrFail($prescriptionId);

            // Delete existing items
            PrescriptionItem::where('prescription_id', $prescriptionId)->delete();

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Find batch using FIFO
                $batch = ProductBatch::where('product_id', $item['product_id'])
                    ->where('quantity_remaining', '>=', $item['quantity'])
                    ->orderBy('expiration_date', 'asc')
                    ->first();

                if (!$batch) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->product_name}"
                    ], 400);
                }

                PrescriptionItem::create([
                    'prescription_id' => $prescriptionId,
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'quantity' => $item['quantity'],
                    'batch_id' => $batch->id  // <-- Assign batch
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Products saved successfully with batches!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving prescription items with batch_id: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error saving prescription items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOrderSummary($orderId)
    {
        try {
            $prescription = Prescription::findOrFail($orderId);
            $prescriptionItems = PrescriptionItem::where('prescription_id', $orderId)
                ->with('product')
                ->get();

            if ($prescriptionItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items found for this prescription'
                ]);
            }

            $items = [];
            $totalAmount = 0;
            $totalItems = 0;

            foreach ($prescriptionItems as $item) {
                if (!$item->product) {
                    continue;
                }

                $latestBatch = ProductBatch::where('product_id', $item->product_id)
                    ->where('quantity_remaining', '>', 0)
                    ->latest('created_at')
                    ->first();

                $unitPrice = $latestBatch ? (float)$latestBatch->sale_price : 0;
                $stockAvailable = ProductBatch::where('product_id', $item->product_id)
                    ->sum('quantity_remaining');

                $itemData = [
                    'product_name' => $item->product->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $item->quantity * $unitPrice,
                    'stock_available' => $stockAvailable
                ];

                $items[] = $itemData;
                $totalAmount += $itemData['subtotal'];
                $totalItems += $item->quantity;
            }

            return response()->json([
                'success' => true,
                'items' => $items,
                'total_items' => $totalItems,
                'total_amount' => $totalAmount
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading order summary: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sales()
    {
        $completedOrders = Order::where('status', 'completed')
            ->with(['items.product', 'customer', 'prescription', 'sale'])
            ->orderByDesc('updated_at')
            ->get();

        $completedOrders->each(function ($order) {
            $total = $order->items->sum(function ($item) {
                $latestBatch = ProductBatch::where('product_id', $item->product_id)
                    ->where('quantity_remaining', '>', 0)
                    ->latest('created_at')
                    ->first();

                $price = $latestBatch ? $latestBatch->sale_price : 0;
                $qty   = $item->approved_quantity ?? $item->quantity;

                return $qty * $price;
            });

            $paymentMethod = $order->sale->payment_method ?? 'cash';

            $order->setAttribute('total_amount', $total);
            $order->setAttribute('sale_date', $order->updated_at ?? $order->created_at);
            $order->setAttribute('payment_method', $paymentMethod);
        });

        $totalSales     = $completedOrders->sum('total_amount');
        $completedCount = $completedOrders->count();

        $todayStart  = now()->startOfDay();
        $todayOrders = $completedOrders->filter(function ($o) use ($todayStart) {
            $dt = $o->sale_date ?? ($o->updated_at ?? $o->created_at);
            return $dt >= $todayStart;
        });

        $todaySales = $todayOrders->sum('total_amount');
        $todayCount = $todayOrders->count();

        $pendingOrders = Order::where('status', 'pending')
            ->with(['items.product', 'sale'])
            ->get();

        $pendingOrders->each(function ($order) {
            $total = $order->items->sum(function ($item) {
                $latestBatch = ProductBatch::where('product_id', $item->product_id)
                    ->where('quantity_remaining', '>', 0)
                    ->latest('created_at')
                    ->first();

                $price = $latestBatch ? $latestBatch->sale_price : 0;
                $qty   = $item->approved_quantity ?? $item->quantity;

                return $qty * $price;
            });

            $order->setAttribute('total_amount', $total);
        });

        $pendingCount = $pendingOrders->count();
        $pendingValue = $pendingOrders->sum('total_amount');

        $averageOrder = $completedCount > 0 ? $totalSales / $completedCount : 0;

        $salesStats = [
            'total_sales'     => $totalSales,
            'completed_count' => $completedCount,
            'today_sales'     => $todaySales,
            'today_count'     => $todayCount,
            'pending_count'   => $pendingCount,
            'pending_value'   => $pendingValue,
            'average_order'   => $averageOrder,
        ];

        $sales = $completedOrders;

        return view('sales.sales', compact('sales', 'salesStats'));
    }

    public function showOrders()
    {
        $orders = Order::with('items.product')->get();
        return response()->json($orders);
    }

    public function debugPrescription($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);
        return response()->json([
            'order' => $order,
            'items' => $order->items,
        ]);
    }

    public function loadPrescriptionItemsEloquent($prescriptionId)
    {
        try {
            $prescription = Prescription::findOrFail($prescriptionId);

            $items = PrescriptionItem::where('prescription_id', $prescriptionId)
                ->with(['product', 'batch'])
                ->get()
                ->map(function ($item) {
                    // Get price from batch if available, otherwise get latest batch price
                    $unitPrice = 0;

                    if ($item->batch) {
                        // Use the assigned batch price
                        $unitPrice = (float)$item->batch->sale_price;
                    } else {
                        // Fallback: get price from latest available batch
                        $latestBatch = ProductBatch::where('product_id', $item->product_id)
                            ->where('quantity_remaining', '>', 0)
                            ->whereNotNull('sale_price')
                            ->orderBy('expiration_date', 'asc')
                            ->orderBy('received_date', 'asc')
                            ->first();

                        $unitPrice = $latestBatch ? (float)$latestBatch->sale_price : 0;
                    }

                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'product_name' => $item->product->product_name ?? 'Unknown Product',
                        'product_price' => $unitPrice
                    ];
                });

            return response()->json([
                'success' => true,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading prescription items: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading prescription items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPrescriptionItems($prescriptionId)
    {
        try {
            $items = PrescriptionItem::where('prescription_id', $prescriptionId)
                ->with('product')
                ->get()
                ->map(function ($item) {
                    // Get the earliest expiring batch for pricing
                    $batch = ProductBatch::where('product_id', $item->product_id)
                        ->where('quantity_remaining', '>', 0)
                        ->whereNotNull('expiration_date')
                        ->orderBy('expiration_date', 'asc')
                        ->orderBy('received_date', 'asc')
                        ->first();

                    $unitPrice = $batch ? (float)$batch->sale_price : 0;

                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'product_name' => $item->product->product_name ?? 'Unknown Product',
                        'product_price' => $unitPrice,
                        'batch_info' => $batch ? [
                            'batch_number' => $batch->batch_number,
                            'expiration_date' => $batch->expiration_date,
                            'quantity_remaining' => $batch->quantity_remaining
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading prescription items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading prescription items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveSelection(Request $request, $prescriptionId)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        try {
            DB::beginTransaction();

            $prescription = Prescription::findOrFail($prescriptionId);
            PrescriptionItem::where('prescription_id', $prescriptionId)->delete();

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check total available stock
                $totalAvailableStock = ProductBatch::where('product_id', $item['product_id'])
                    ->where('quantity_remaining', '>', 0)
                    ->sum('quantity_remaining');

                if ($totalAvailableStock < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$product->product_name}. Available: {$totalAvailableStock}, Required: {$item['quantity']}"
                    ], 400);
                }

                // Find earliest expiring batch with stock (FIFO)
                $earliestBatch = ProductBatch::where('product_id', $item['product_id'])
                    ->where('quantity_remaining', '>', 0)
                    ->whereNotNull('expiration_date')
                    ->orderBy('expiration_date', 'asc')
                    ->orderBy('received_date', 'asc') // Secondary sort for same expiration dates
                    ->first();

                if (!$earliestBatch) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "No available batches found for {$product->product_name}"
                    ], 400);
                }

                // Create prescription item with batch tracking
                $itemData = [
                    'prescription_id' => $prescriptionId,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'product_name' => $product->product_name
                ];

                // Add batch reference if your prescription_items table has it
                if (Schema::hasColumn('prescription_items', 'batch_id')) {
                    $itemData['batch_id'] = $earliestBatch->id;
                }

                if (Schema::hasColumn('prescription_items', 'batch_number')) {
                    $itemData['batch_number'] = $earliestBatch->batch_number;
                }

                PrescriptionItem::create($itemData);

                Log::info("Selected earliest expiring batch", [
                    'product' => $product->product_name,
                    'batch_number' => $earliestBatch->batch_number,
                    'expiration_date' => $earliestBatch->expiration_date,
                    'quantity_remaining' => $earliestBatch->quantity_remaining,
                    'selected_quantity' => $item['quantity']
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Products saved successfully with FIFO batch selection!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveSelection: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

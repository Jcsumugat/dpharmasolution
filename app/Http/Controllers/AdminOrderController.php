<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

        // Products that still have stock in batches
        $products = Product::whereHas('batches', function ($q) {
            $q->where('quantity_remaining', '>', 0);
        })->get();

        return view('orders.orders', compact('prescriptions', 'products'));
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
                    $query->select('id', 'product_name'); // Removed unit_price as it doesn't exist
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

                // Get sale price from the latest batch or fallback to 0
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

            // Stock check from product_batches
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

            // Deduct stock from batches FIFO
            foreach ($prescriptionItems as $prescItem) {
                $product = $prescItem->product ?? Product::find($prescItem->product_id);

                // Get sale price from the latest batch
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
                    ->orderBy('expiration_date', 'asc') // Fixed column name
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

            // Only call notification services if they exist
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

    public function approve(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:prescriptions,id',
            'message' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $prescription = Prescription::with('order')->findOrFail($request->id);
            if ($prescription->order) {
                $prescription->order->update(['status' => 'approved']);
            }

            $prescription->update([
                'status' => 'approved',
                'admin_message' => $request->message,
                'updated_at' => now()
            ]);

            if (class_exists('App\Services\NotificationService')) {
                NotificationService::notifyOrderApproved($prescription);
            }

            DB::commit();
            return back()->with('success', 'Order approved.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error approving order: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve order. Please try again.');
        }
    }

    public function partialApprove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:prescriptions,id',
            'message' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $prescription = Prescription::with('order')->findOrFail($request->id);
            if ($prescription->order) {
                $prescription->order->update(['status' => 'partially_approved']);
            }

            $prescription->update([
                'status' => 'partially_approved',
                'admin_message' => $request->message,
                'updated_at' => now()
            ]);

            DB::commit();
            return back()->with('info', 'Order partially approved.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error partially approving order: ' . $e->getMessage());
            return back()->with('error', 'Failed to partially approve order. Please try again.');
        }
    }

    /**
     * Save Admin's Approved Items
     */
    public function saveSelection(Request $request, $orderId)
    {
        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                $orderItem = OrderItem::find($item['order_item_id']);
                if ($orderItem) {
                    $orderItem->approved_quantity = $item['approved_quantity'];
                    $orderItem->status = 'approved';
                    $orderItem->save();

                    // Deduct stock from product_batches (FIFO)
                    $remainingQty = $item['approved_quantity'];
                    $batches = ProductBatch::where('product_id', $orderItem->product_id)
                        ->where('quantity_remaining', '>', 0) // Fixed column name
                        ->orderBy('expiration_date', 'asc') // Fixed column name
                        ->get();

                    foreach ($batches as $batch) {
                        if ($remainingQty <= 0) break;

                        $deduct = min($batch->quantity_remaining, $remainingQty); // Fixed column name
                        $batch->quantity_remaining -= $deduct; // Fixed column name
                        $batch->save();

                        $remainingQty -= $deduct;
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Selection saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error saving selection.']);
        }
    }

    /**
     * Get Prescription Items
     */
    public function getPrescriptionItems($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);
        return response()->json($order->items);
    }

    /**
     * Get Order Summary
     */
    public function getOrderSummary($orderId)
    {
        try {
            // Get prescription items instead of order items since that's what's being used
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

                // Get price from latest batch
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
    // Completed orders for the table with sales relationship
    $completedOrders = Order::where('status', 'completed')
        ->with(['items.product', 'customer', 'prescription', 'sale'])
        ->orderByDesc('updated_at')
        ->get();

    // Compute total_amount per order, sale_date, and payment_method
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

        // Get payment_method from the related sale record
        $paymentMethod = $order->sale->payment_method ?? 'cash';

        // Add dynamic attributes for Blade
        $order->setAttribute('total_amount', $total);
        $order->setAttribute('sale_date', $order->updated_at ?? $order->created_at);
        $order->setAttribute('payment_method', $paymentMethod);
    });

    // === Stats ===
    $totalSales     = $completedOrders->sum('total_amount');
    $completedCount = $completedOrders->count();

    $todayStart  = now()->startOfDay();
    $todayOrders = $completedOrders->filter(function ($o) use ($todayStart) {
        $dt = $o->sale_date ?? ($o->updated_at ?? $o->created_at);
        return $dt >= $todayStart;
    });

    $todaySales = $todayOrders->sum('total_amount');
    $todayCount = $todayOrders->count();

    // Pending orders for pending stats
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

    // What the Blade iterates over
    $sales = $completedOrders;

    return view('sales.sales', compact('sales', 'salesStats'));
}



    /**
     * Show All Orders
     */
    public function showOrders()
    {
        $orders = Order::with('items.product')->get();
        return response()->json($orders);
    }

    /**
     * Debug Prescription (for testing)
     */
    public function debugPrescription($orderId)
    {
        $order = Order::with('items.product')->findOrFail($orderId);
        return response()->json([
            'order' => $order,
            'items' => $order->items,
        ]);
    }
}

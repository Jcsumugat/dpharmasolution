<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    // Show the inventory with search & sort (excluding expired batches)
    public function index(Request $request)
    {
        $query = Product::with(['batches' => function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->orderBy('expiration_date', 'asc');
        }, 'supplier']);

        // Add batch count and totals (excluding expired batches)
        $query->withCount(['batches as available_batches_count' => function ($q) {
            $q->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        }])
            ->addSelect([
                'available_stock' => ProductBatch::selectRaw('COALESCE(SUM(quantity_remaining), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now()), // Exclude expired batches
                'earliest_available_expiration' => ProductBatch::select('expiration_date')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now()) // Exclude expired batches
                    ->orderBy('expiration_date', 'asc')
                    ->limit(1),
                'current_sale_price' => ProductBatch::select('sale_price')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now()) // Exclude expired batches
                    ->orderBy('expiration_date', 'asc')
                    ->orderBy('received_date', 'asc')
                    ->limit(1)
            ]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('product_name', 'like', '%' . $request->search . '%')
                    ->orWhere('product_code', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting logic
        $direction = $request->get('direction', 'asc');

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'name':
                    $query->orderBy('product_name', $direction);
                    break;
                case 'price':
                    $query->orderBy('current_sale_price', $direction);
                    break;
                case 'quantity':
                    $query->orderBy('available_stock', $direction);
                    break;
                case 'expiry':
                    $query->orderBy('earliest_available_expiration', $direction);
                    break;
                default:
                    $query->orderBy('product_name', 'asc');
            }
        } else {
            $query->orderBy('product_name', 'asc');
        }

        $products = $query->get();

        // Get suppliers for the batch modal
        $suppliers = Supplier::orderBy('name')->get();

        return view('inventory.inventory', compact('products', 'suppliers'));
    }


    // View batches for a product (show all batches but highlight expired ones)
    public function viewBatches(Product $product)
    {
        // Get all batches, but separate available from expired
        $availableBatches = $product->batches()
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '>', now())
            ->orderBy('expiration_date', 'asc')
            ->get();

        $expiredBatches = $product->batches()
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '<=', now())
            ->orderBy('expiration_date', 'desc')
            ->get();

        // Combine for display but mark their status
        $batches = $availableBatches->map(function ($batch) {
            $batch->setAttribute('is_expired', false);
            $batch->setAttribute('status_info', $batch->getExpirationStatus());
            return $batch;
        })->concat($expiredBatches->map(function ($batch) {
            $batch->setAttribute('is_expired', true);
            $batch->setAttribute('status_info', $batch->getExpirationStatus());
            return $batch;
        }));

        return view('inventory.batches-modal', compact('product', 'batches', 'availableBatches', 'expiredBatches'));
    }


    // Get stock out reasons for dropdown
    public function getStockOutReasons()
    {
        $reasons = [
            'sale' => 'Sale/Customer Purchase',
            'damage' => 'Damaged/Defective',
            'expired' => 'Expired Product',
            'theft' => 'Theft/Loss',
            'adjustment' => 'Inventory Adjustment',
            'return' => 'Return to Supplier',
            'transfer' => 'Transfer to Another Location',
            'sample' => 'Sample/Testing',
            'other' => 'Other Reason'
        ];

        return response()->json(['reasons' => $reasons]);
    }

    // Helper method to generate batch numbers
    private function generateBatchNumber(Product $product)
    {
        $prefix = strtoupper(substr($product->product_code ?: 'PRD', 0, 3));
        $date = now()->format('Ymd');
        $lastBatch = ProductBatch::where('product_id', $product->id)
            ->where('batch_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('batch_number', 'desc')
            ->first();

        if ($lastBatch) {
            $lastNumber = intval(substr($lastBatch->batch_number, -3));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$prefix}-{$date}-{$newNumber}";
    }

    // Helper method to get reason text
    private function getReasonText($reason)
    {
        $reasons = [
            'sale' => 'Sale/Customer Purchase',
            'damage' => 'Damaged/Defective',
            'expired' => 'Expired Product',
            'theft' => 'Theft/Loss',
            'adjustment' => 'Inventory Adjustment',
            'return' => 'Return to Supplier',
            'transfer' => 'Transfer to Another Location',
            'sample' => 'Sample/Testing',
            'other' => 'Other Reason'
        ];

        return $reasons[$reason] ?? 'Unknown Reason';
    }

    // Helper method to get stock movement type based on reason
    private function getStockMovementType($reason)
    {
        $typeMapping = [
            'sale' => StockMovement::TYPE_SALE ?? 'sale',
            'damage' => StockMovement::TYPE_DAMAGE ?? 'damage',
            'expired' => StockMovement::TYPE_EXPIRED ?? 'expired',
            'theft' => StockMovement::TYPE_LOSS ?? 'loss',
            'adjustment' => StockMovement::TYPE_ADJUSTMENT ?? 'adjustment',
            'return' => StockMovement::TYPE_RETURN ?? 'return',
            'transfer' => StockMovement::TYPE_TRANSFER ?? 'transfer',
            'sample' => StockMovement::TYPE_SAMPLE ?? 'sample',
            'other' => StockMovement::TYPE_OTHER ?? 'other'
        ];

        return $typeMapping[$reason] ?? 'other';
    }

    // Get product stock status including expired batch information
    public function getProductStockStatus($productId)
    {
        try {
            $product = Product::with(['batches' => function ($query) {
                $query->where('quantity_remaining', '>', 0)
                    ->orderBy('expiration_date', 'asc');
            }])->findOrFail($productId);

            $availableStock = $product->batches
                ->where('expiration_date', '>', now())
                ->sum('quantity_remaining');

            $expiredStock = $product->batches
                ->where('expiration_date', '<=', now())
                ->sum('quantity_remaining');

            $nearExpiryStock = $product->batches
                ->where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays(30))
                ->sum('quantity_remaining');

            return response()->json([
                'success' => true,
                'product_name' => $product->product_name,
                'available_stock' => $availableStock,
                'expired_stock' => $expiredStock,
                'near_expiry_stock' => $nearExpiryStock,
                'total_stock' => $availableStock + $expiredStock,
                'is_low_stock' => $availableStock <= $product->reorder_level,
                'reorder_level' => $product->reorder_level,
                'status' => $this->getStockStatusText($availableStock, $expiredStock, $product->reorder_level)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting stock status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Helper method to get stock status text
    private function getStockStatusText($availableStock, $expiredStock, $reorderLevel)
    {
        if ($availableStock <= 0 && $expiredStock > 0) {
            return "Out of stock - {$expiredStock} units expired";
        } elseif ($availableStock <= 0) {
            return "Out of stock";
        } elseif ($reorderLevel && $availableStock <= $reorderLevel) {
            $message = "Low stock - {$availableStock} units available";
            if ($expiredStock > 0) {
                $message .= " ({$expiredStock} expired)";
            }
            return $message;
        } else {
            $message = "In stock - {$availableStock} units available";
            if ($expiredStock > 0) {
                $message .= " ({$expiredStock} expired)";
            }
            return $message;
        }
    }

    // Get expired batches for cleanup
    public function getExpiredBatches()
    {
        try {
            $expiredBatches = ProductBatch::with('product')
                ->where('expiration_date', '<=', now())
                ->where('quantity_remaining', '>', 0)
                ->orderBy('expiration_date')
                ->get()
                ->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'product_name' => $batch->product->product_name,
                        'batch_number' => $batch->batch_number,
                        'expiration_date' => $batch->expiration_date->format('M d, Y'),
                        'days_expired' => abs(now()->diffInDays($batch->expiration_date, false)),
                        'quantity_remaining' => $batch->quantity_remaining,
                        'unit_cost' => $batch->unit_cost,
                        'total_value' => $batch->quantity_remaining * $batch->unit_cost
                    ];
                });

            return response()->json([
                'success' => true,
                'expired_batches' => $expiredBatches,
                'total_expired_quantity' => $expiredBatches->sum('quantity_remaining'),
                'total_expired_value' => $expiredBatches->sum('total_value')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting expired batches: ' . $e->getMessage()
            ], 500);
        }
    }
    public function addBatch(Request $request, Product $product)
    {
        $request->validate([
            'quantity_received' => 'required|integer|min:1',
            'expiration_date' => 'required|date|after:today',
            'unit_cost' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'received_date' => 'required|date|before_or_equal:today',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Create new batch using the enhanced method
            $batch = ProductBatch::createWithMovement([
                'product_id' => $product->id,
                'batch_number' => $this->generateBatchNumber($product),
                'quantity_received' => $request->quantity_received,
                'quantity_remaining' => $request->quantity_received,
                'expiration_date' => $request->expiration_date,
                'unit_cost' => $request->unit_cost,
                'sale_price' => $request->sale_price,
                'received_date' => $request->received_date,
                'supplier_id' => $request->supplier_id ?: $product->supplier_id,
                'notes' => $request->notes,
            ], StockMovement::REFERENCE_PURCHASE, null);

            // Add quantity_received to product's stock_quantity
            $product->increment('stock_quantity', $request->quantity_received);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "New batch added successfully! Batch #{$batch->batch_number} with {$request->quantity_received} units.",
                    'batch' => $batch->getBatchInfo(),
                    'updated_stock' => $product->fresh()->stock_quantity
                ]);
            }

            return redirect()->route('inventory.index')
                ->with('stock_action', "New batch added successfully! Batch #{$batch->batch_number} with {$request->quantity_received} units.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding batch: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding batch: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error adding batch: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Updated stockOut method - now automatically updates stock_quantity
    public function stockOut(Request $request)
    {
        $request->validate([
            'batch_id' => 'nullable|exists:product_batches,id',
            'product_id' => 'required|exists:products,id',
            'stock_out' => 'required|integer|min:1',
            'reason' => 'required|string|in:sale,damage,expired,theft,adjustment,return,transfer,sample,other',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            $quantity = $request->stock_out;

            // If batch_id is provided, remove from specific batch
            if ($request->batch_id) {
                $batch = ProductBatch::findOrFail($request->batch_id);

                // Prevent stock out from expired batches unless reason is 'expired'
                if ($batch->isExpired() && $request->reason !== 'expired') {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove stock from expired batch {$batch->batch_number}. Use 'Expired Product' reason to dispose of expired stock."
                    ], 400);
                }

                // Validate quantity
                if ($quantity > $batch->quantity_remaining) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove {$quantity} units. Only {$batch->quantity_remaining} units available in this batch."
                    ], 400);
                }

                // Use the batch's built-in method to reduce quantity - this automatically updates product stock
                $batch->reduceQuantity(
                    $quantity,
                    $this->getStockMovementType($request->reason),
                    $this->getReasonText($request->reason) . ($request->notes ? ' - ' . $request->notes : ''),
                    StockMovement::REFERENCE_MANUAL,
                    null
                );

                $message = "Successfully removed {$quantity} units from batch {$batch->batch_number}.";
            } else {
                // Remove from product using FIFO (excluding expired batches unless reason is 'expired')
                $availableStock = $product->available_stock;

                if ($request->reason !== 'expired' && $quantity > $availableStock) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove {$quantity} from {$product->product_name}. Only {$availableStock} units of non-expired stock available."
                    ], 400);
                }

                // Get batch allocation
                if ($request->reason === 'expired') {
                    // For expired stock removal, use expired batches
                    $batches = $product->batches()
                        ->where('quantity_remaining', '>', 0)
                        ->where('expiration_date', '<=', now())
                        ->orderBy('expiration_date', 'asc')
                        ->get();
                } else {
                    // For normal stock out, use available (non-expired) batches
                    $batches = $product->batches()
                        ->where('quantity_remaining', '>', 0)
                        ->where('expiration_date', '>', now())
                        ->orderBy('expiration_date', 'asc')
                        ->orderBy('received_date', 'asc')
                        ->get();
                }

                $remainingToRemove = $quantity;
                $batchesUsed = [];

                foreach ($batches as $batch) {
                    if ($remainingToRemove <= 0) break;

                    $removeFromBatch = min($remainingToRemove, $batch->quantity_remaining);

                    // Use batch method - this automatically updates product stock
                    $batch->reduceQuantity(
                        $removeFromBatch,
                        $this->getStockMovementType($request->reason),
                        $this->getReasonText($request->reason) . ($request->notes ? ' - ' . $request->notes : ''),
                        StockMovement::REFERENCE_MANUAL,
                        null
                    );

                    $batchesUsed[] = "{$batch->batch_number} ({$removeFromBatch} units)";
                    $remainingToRemove -= $removeFromBatch;
                }

                if ($remainingToRemove > 0) {
                    throw new \Exception("Could not fulfill complete stock out request. {$remainingToRemove} units remaining.");
                }

                $message = "Removed {$quantity} units from {$product->product_name} using " . count($batchesUsed) . " batch(es).";
            }

            DB::commit();

            // Get fresh stock count after automatic update
            $updatedStock = $product->fresh()->available_stock;

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'updated_stock' => $updatedStock
                ]);
            }

            return redirect()->route('inventory.index')
                ->with('stock_action', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in stock out: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error removing stock: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error removing stock: ' . $e->getMessage());
        }
    }
    public function addStockToBatch(Request $request, ProductBatch $batch)
{
    $request->validate([
        'additional_quantity' => 'required|integer|min:1',
        'unit_cost' => 'required|numeric|min:0',
        'received_date' => 'required|date|before_or_equal:today',
        'notes' => 'nullable|string|max:1000',
    ]);

    // Verify unit cost matches
    if ($request->unit_cost != $batch->unit_cost) {
        return back()->withErrors(['unit_cost' => 'Unit cost must match the existing batch unit cost.']);
    }

    DB::beginTransaction();
    try {
        // Update batch quantities
        $batch->increment('quantity_received', $request->additional_quantity);
        $batch->increment('quantity_remaining', $request->additional_quantity);

        // Update product total stock
        $batch->product->increment('stock_quantity', $request->additional_quantity);

        // Create stock movement record
        StockMovement::create([
            'product_id' => $batch->product_id,
            'batch_id' => $batch->id,
            'type' => 'stock_addition',
            'quantity' => $request->additional_quantity,
            'notes' => $request->notes,
        ]);

        DB::commit();

        return back()->with('success', "Successfully added {$request->additional_quantity} units to batch #{$batch->batch_number}");
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error adding stock: ' . $e->getMessage());
    }
}
}

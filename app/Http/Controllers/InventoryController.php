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
    // Fixed generateBatchNumber helper method
    private function generateBatchNumber($productId)
    {
        $product = Product::findOrFail($productId);
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product->product_name), 0, 3));

        if (strlen($prefix) < 3) {
            $prefix = str_pad($prefix, 3, 'X');
        }

        $date = now()->format('ymd');
        $existingBatches = ProductBatch::where('product_id', $productId)
            ->where('batch_number', 'like', "{$prefix}{$date}%")
            ->count();

        $sequence = str_pad($existingBatches + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}{$date}{$sequence}";
    }

    public function addBatch(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity_received' => 'required|integer|min:1',
            'expiration_date' => 'required|date|after:today',
            'received_date' => 'required|date|before_or_equal:today',
            'unit_cost' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',

            // NEW: Unit override fields (optional)
            'unit' => 'nullable|string|in:bottle,ml,L,vial,ampoule,dropper_bottle,nebule,tablet,capsule,blister_pack,box,strip,sachet,syringe,injection_vial,injection_ampoule,tube,jar,topical_bottle,inhaler,patch,suppository,piece,pack',
            'unit_quantity' => 'nullable|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $batchNumber = $this->generateBatchNumber($product->id);

            $batchData = [
                'product_id' => $product->id,
                'batch_number' => $batchNumber,
                'quantity_received' => $validated['quantity_received'],
                'quantity_remaining' => $validated['quantity_received'],
                'expiration_date' => $validated['expiration_date'],
                'received_date' => $validated['received_date'],
                'unit_cost' => $validated['unit_cost'],
                'sale_price' => $validated['sale_price'] ?? $product->getCurrentSalePriceAttribute(),
                'supplier_id' => $validated['supplier_id'] ?? $product->supplier_id,
                'notes' => $validated['notes'] ?? null,
            ];

            // Add unit override only if provided
            if (!empty($validated['unit'])) {
                $batchData['unit'] = $validated['unit'];
                $batchData['unit_quantity'] = $validated['unit_quantity'] ?? 1;
            }

            $batch = ProductBatch::create($batchData);

            StockMovement::createMovement(
                $product->id,
                StockMovement::TYPE_PURCHASE,
                $validated['quantity_received'],
                StockMovement::REFERENCE_PURCHASE,
                null,
                "New batch received - Batch: {$batchNumber}",
                $batch->id
            );

            $product->updateCachedFields();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch added successfully',
                'batch' => $batch->load('product', 'supplier'),
                'unit_display' => $batch->getUnitDisplay(),
                'has_override' => $batch->hasUnitOverride(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add batch', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add batch: ' . $e->getMessage()
            ], 500);
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
    /**
     * Add stock to an existing batch
     */
    public function addStockToBatch(Request $request, ProductBatch $batch)
    {
        try {
            $request->validate([
                'additional_quantity' => 'required|integer|min:1',
                'unit_cost' => 'required|numeric|min:0.01',
                'received_date' => 'required|date|before_or_equal:today',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Calculate new weighted average cost
            $currentTotalValue = $batch->quantity_received * $batch->unit_cost;
            $additionalTotalValue = $request->additional_quantity * $request->unit_cost;
            $newTotalQuantity = $batch->quantity_received + $request->additional_quantity;
            $newWeightedAvgCost = ($currentTotalValue + $additionalTotalValue) / $newTotalQuantity;

            // Update batch quantities and cost
            $batch->update([
                'quantity_received' => $newTotalQuantity,
                'quantity_remaining' => $batch->quantity_remaining + $request->additional_quantity,
                'unit_cost' => round($newWeightedAvgCost, 2),
                'received_date' => $request->received_date,
                'notes' => $request->notes
            ]);

            // Update product total stock
            $batch->product->increment('stock_quantity', $request->additional_quantity);

            // Create stock movement record
            StockMovement::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'type' => 'stock_addition',
                'quantity' => $request->additional_quantity,
                'notes' => "Added {$request->additional_quantity} units to batch #{$batch->batch_number}" .
                    ($request->notes ? " - {$request->notes}" : ''),
                'reference_type' => StockMovement::REFERENCE_MANUAL ?? 'manual',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully added {$request->additional_quantity} units to batch #{$batch->batch_number}",
                    'data' => [
                        'batch_id' => $batch->id,
                        'new_quantity_received' => $batch->fresh()->quantity_received,
                        'new_quantity_remaining' => $batch->fresh()->quantity_remaining,
                        'new_unit_cost' => round($newWeightedAvgCost, 2),
                        'product_stock_quantity' => $batch->product->fresh()->stock_quantity
                    ],
                    'reload' => true
                ]);
            }

            return redirect()->back()->with('success', "Successfully added {$request->additional_quantity} units to batch #{$batch->batch_number}");
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::error('Validation failed for add stock to batch', [
                'batch_id' => $batch->id ?? 'unknown',
                'batch_number' => $batch->batch_number ?? 'unknown',
                'product_id' => $batch->product_id ?? 'unknown',
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . $e->validator->errors()->first(),
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error adding stock to batch', [
                'batch_id' => $batch->id ?? 'unknown',
                'batch_number' => $batch->batch_number ?? 'unknown',
                'product_id' => $batch->product_id ?? 'unknown',
                'product_name' => $batch->product->name ?? 'unknown',
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server error occurred while adding stock. Please try again.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Error adding stock: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update batch pricing
     */
    public function updateBatchPrice(Request $request, ProductBatch $batch)
    {
        try {
            $request->validate([
                'unit_cost' => 'required|numeric|min:0.01',
                'sale_price' => 'required|numeric|min:0.01',
                'notes' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $oldSalePrice = $batch->sale_price;
            $oldUnitCost = $batch->unit_cost;

            // Update batch pricing
            $batch->update([
                'unit_cost' => $request->unit_cost,
                'sale_price' => $request->sale_price,
            ]);

            // Create stock movement record for price update
            StockMovement::create([
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'type' => 'price_update',
                'quantity' => 0, // No quantity change for price updates
                'notes' => "Price updated for batch #{$batch->batch_number}. " .
                    "Unit cost: ₱{$oldUnitCost} → ₱{$request->unit_cost}, " .
                    "Sale price: ₱{$oldSalePrice} → ₱{$request->sale_price}" .
                    ($request->notes ? " - {$request->notes}" : ''),
                'reference_type' => StockMovement::REFERENCE_MANUAL ?? 'manual',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully updated pricing for batch #{$batch->batch_number}",
                    'data' => [
                        'batch_id' => $batch->id,
                        'new_unit_cost' => $batch->fresh()->unit_cost,
                        'new_sale_price' => $batch->fresh()->sale_price,
                        'margin' => $batch->fresh()->unit_cost > 0 ?
                            (($batch->fresh()->sale_price - $batch->fresh()->unit_cost) / $batch->fresh()->unit_cost) * 100 : 0
                    ],
                    'reload' => true
                ]);
            }

            return redirect()->back()->with('success', "Successfully updated pricing for batch #{$batch->batch_number}");
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::error('Validation failed for batch price update', [
                'batch_id' => $batch->id ?? 'unknown',
                'batch_number' => $batch->batch_number ?? 'unknown',
                'product_id' => $batch->product_id ?? 'unknown',
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error: ' . $e->validator->errors()->first(),
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating batch price', [
                'batch_id' => $batch->id ?? 'unknown',
                'batch_number' => $batch->batch_number ?? 'unknown',
                'product_id' => $batch->product_id ?? 'unknown',
                'product_name' => $batch->product->name ?? 'unknown',
                'request_data' => $request->all(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server error occurred while updating price. Please try again.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating price: ' . $e->getMessage())->withInput();
        }
    }
}

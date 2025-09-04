<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with([
            'supplier',
            'category',
            'batches' => function ($query) {
                $query->where('quantity_remaining', '>', 0)
                    ->orderBy('expiration_date');
            }
        ])
            ->withCount('batches')
            ->orderBy('created_at', 'desc')
            ->get();

        $products->each(function ($product) {
            $availableBatches = $product->batches->where('quantity_remaining', '>', 0);
            $product->earliest_expiration = $availableBatches->min('expiration_date');
            $product->latest_expiration = $availableBatches->max('expiration_date');
        });

        $suppliers = Supplier::orderBy('name')->get();

        return view('products.products', compact('products', 'suppliers'));
    }

    public function customerIndex()
    {
        $products = Product::whereHas('batches', function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        })
            ->with(['batches' => function ($query) {
                $query->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now())
                    ->orderBy('expiration_date', 'asc');
            }])
            ->orderBy('product_name')
            ->get();

        return view('client.products', compact('products'));
    }

    private function generateUniqueProductCode($maxAttempts = 100)
    {
        $attempts = 0;

        do {
            $attempts++;
            $product_code = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Product::where('product_code', $product_code)->exists();

            if ($attempts >= $maxAttempts) {
                $product_code = substr(time(), -4);
                if (Product::where('product_code', $product_code)->exists()) {
                    $product_code = $product_code . rand(0, 9);
                }
                break;
            }
        } while ($exists);

        Log::info('Product code generated', [
            'product_code' => $product_code,
            'attempts' => $attempts
        ]);

        return $product_code;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'brand_name' => 'nullable|string|max:100',
            'generic_name' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\-\.]+$/',
            'product_type' => 'required|string|max:50',
            'dosage_strength' => 'nullable|string|max:10',
            'dosage_unit' => 'required|string|max:50',
            'form_type' => 'required|string|max:50',
            'storage_requirements' => 'nullable|string|max:100',
            'classification' => 'required|integer|min:1|max:13',
            'reorder_level' => 'required|integer|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        try {
            DB::beginTransaction();

            $product_code = $this->generateUniqueProductCode();

            // Combine dosage strength and unit
            $combinedDosage = '';
            if (!empty($validated['dosage_strength']) && !empty($validated['dosage_unit'])) {
                $combinedDosage = $validated['dosage_strength'] . $validated['dosage_unit'];
            } elseif (!empty($validated['dosage_unit'])) {
                $combinedDosage = $validated['dosage_unit'];
            } elseif (!empty($validated['dosage_strength'])) {
                $combinedDosage = $validated['dosage_strength'];
            }

            $product = Product::create([
                'product_name' => $validated['product_name'],
                'manufacturer' => $validated['manufacturer'],
                'brand_name' => $validated['brand_name'],
                'product_type' => $validated['product_type'],
                'generic_name' => $validated['generic_name'] ? ucwords(strtolower($validated['generic_name'])) : null,
                'dosage_unit' => $combinedDosage, // Store combined value
                'form_type' => $validated['form_type'],
                'storage_requirements' => $validated['storage_requirements'],
                'classification' => $validated['classification'],
                'reorder_level' => $validated['reorder_level'],
                'supplier_id' => $validated['supplier_id'],
                'category_id' => $validated['category_id'],
                'product_code' => $product_code,
            ]);

            DB::commit();

            Log::info('Product created successfully', [
                'product_id' => $product->id,
                'product_code' => $product->product_code,
                'product_name' => $product->product_name,
                'combined_dosage' => $combinedDosage,
            ]);

            return redirect()->route('products.index')
                ->with('success', "Product '{$product->product_name}' created successfully! (Code: {$product->product_code}). You can now add inventory batches.");
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'product_name' => $validated['product_name'] ?? 'Unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }



    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'product_name' => 'required|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'brand_name' => 'nullable|string|max:100',
            'generic_name' => 'nullable|string|max:100|regex:/^[a-zA-Z\s\-\.]+$/',
            'product_type' => 'required|string|max:50',
            'dosage_strength' => 'nullable|string|max:10',
            'dosage_unit' => 'nullable|string|max:50',
            'form_type' => 'required|string|max:50',
            'storage_requirements' => 'nullable|string|max:100',
            'classification' => 'nullable|string|max:50',
            'reorder_level' => 'required|integer|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $combinedDosage = '';
            if (!empty($validated['dosage_unit'])) {
                // Use the combined value from hidden field
                $combinedDosage = $validated['dosage_unit'];
            } else {
                // Fallback: combine manually if hidden field is empty
                if (!empty($validated['dosage_strength']) && !empty($validated['unit_type'])) {
                    $combinedDosage = $validated['dosage_strength'] . $validated['unit_type'];
                } elseif (!empty($validated['unit_type'])) {
                    $combinedDosage = $validated['unit_type'];
                } elseif (!empty($validated['dosage_strength'])) {
                    $combinedDosage = $validated['dosage_strength'];
                }
            }

            $updateData = [
                'product_name' => $validated['product_name'],
                'manufacturer' => $validated['manufacturer'],
                'brand_name' => $validated['brand_name'],
                'product_type' => $validated['product_type'],
                'generic_name' => $validated['generic_name'] ? ucwords(strtolower($validated['generic_name'])) : null,
                'dosage_unit' => $combinedDosage, // Store combined value
                'form_type' => $validated['form_type'],
                'storage_requirements' => $validated['storage_requirements'],
                'classification' => $validated['classification'],
                'reorder_level' => $validated['reorder_level'],
                'supplier_id' => $validated['supplier_id'],
                'category_id' => $validated['category_id'],
            ];

            $product->update($updateData);

            Log::info('Product updated successfully', [
                'product_id' => $product->id,
                'product_code' => $product->product_code,
                'combined_dosage' => $combinedDosage,
            ]);

            return redirect()->route('products.index')
                ->with('success', "Product '{$product->product_name}' updated successfully!");
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        try {
            DB::beginTransaction();

            $product->batches()->delete();
            StockMovement::where('product_id', $id)->delete();

            $productName = $product->product_name;
            $product->delete();

            DB::commit();

            Log::info('Product deleted successfully', [
                'product_id' => $id,
                'product_name' => $productName,
            ]);

            return redirect()->route('products.index')
                ->with('success', "Product '{$productName}' and all its batches deleted successfully!");
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to delete product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    public function addBatch(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'batch_quantity' => 'required|integer|min:1',
            'batch_expiration_date' => 'required|date|after:today',
            'batch_unit_cost' => 'required|numeric|min:0',
            'batch_sale_price' => 'required|numeric|min:0',
            'batch_received_date' => 'required|date|before_or_equal:today',
            'batch_supplier_id' => 'nullable|exists:suppliers,id',
            'batch_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $batchNumber = $this->generateBatchNumber($productId);

            $batch = ProductBatch::create([
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'expiration_date' => $validated['batch_expiration_date'],
                'quantity_received' => $validated['batch_quantity'],
                'quantity_remaining' => $validated['batch_quantity'],
                'unit_cost' => $validated['batch_unit_cost'],
                'sale_price' => $validated['batch_sale_price'],
                'received_date' => $validated['batch_received_date'],
                'supplier_id' => $validated['batch_supplier_id'] ?? $product->supplier_id,
                'notes' => $validated['batch_notes'],
            ]);

            StockMovement::createMovement(
                $productId,
                StockMovement::TYPE_PURCHASE,
                $validated['batch_quantity'],
                StockMovement::REFERENCE_PURCHASE,
                null,
                "New batch received - Batch: {$batchNumber}",
                $batch->id
            );

            $product->updateCachedFields();

            DB::commit();

            Log::info('Batch added successfully', [
                'product_id' => $productId,
                'batch_number' => $batchNumber,
                'quantity' => $validated['batch_quantity'],
                'unit_cost' => $validated['batch_unit_cost'],
                'sale_price' => $validated['batch_sale_price']
            ]);

            return response()->json([
                'success' => true,
                'message' => "New batch added successfully! Batch: {$batchNumber}",
                'batch' => [
                    'batch_number' => $batchNumber,
                    'quantity' => $validated['batch_quantity'],
                    'expiration_date' => $validated['batch_expiration_date'],
                    'unit_cost' => $validated['batch_unit_cost'],
                    'sale_price' => $validated['batch_sale_price']
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to add batch', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add batch: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processSale($productId, $quantity, $saleId = null)
    {
        $product = Product::findOrFail($productId);

        $batchAllocation = $product->getBatchesForQuantity($quantity);

        if (!$batchAllocation['can_fulfill']) {
            throw new \Exception("Insufficient stock. Available: {$batchAllocation['available']}, Requested: {$quantity}, Short by: {$batchAllocation['shortage']} units.");
        }

        try {
            DB::beginTransaction();

            $batchesUsed = [];
            $totalRevenue = 0;
            $totalCost = 0;

            foreach ($batchAllocation['batches'] as $allocation) {
                $batch = $allocation['batch'];
                $quantityFromBatch = $allocation['quantity'];

                $batch->decrement('quantity_remaining', $quantityFromBatch);

                $batchRevenue = $quantityFromBatch * $batch->sale_price;
                $batchCost = $quantityFromBatch * $batch->unit_cost;
                $totalRevenue += $batchRevenue;
                $totalCost += $batchCost;

                StockMovement::createMovement(
                    $productId,
                    StockMovement::TYPE_SALE,
                    -$quantityFromBatch,
                    StockMovement::REFERENCE_SALE,
                    $saleId,
                    "Sale - FIFO allocation from Batch: {$batch->batch_number} (₱{$batch->sale_price}/unit)",
                    $batch->id
                );

                $batchesUsed[] = [
                    'batch_number' => $batch->batch_number,
                    'quantity_used' => $quantityFromBatch,
                    'unit_cost' => $batch->unit_cost,
                    'sale_price' => $batch->sale_price,
                    'revenue' => $batchRevenue,
                    'cost' => $batchCost,
                    'profit' => $batchRevenue - $batchCost,
                    'remaining' => $batch->quantity_remaining
                ];
            }

            $product->updateCachedFields();

            DB::commit();

            Log::info('Sale processed successfully', [
                'product_id' => $productId,
                'sale_id' => $saleId,
                'quantity_sold' => $quantity,
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'profit' => $totalRevenue - $totalCost,
                'batches_used' => count($batchesUsed)
            ]);

            return [
                'success' => true,
                'batches_used' => count($batchesUsed),
                'batches_detail' => $batchesUsed,
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'profit' => $totalRevenue - $totalCost,
                'message' => "Sale processed using " . count($batchesUsed) . " batch(es), Revenue: ₱" . number_format($totalRevenue, 2)
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to process sale', [
                'product_id' => $productId,
                'sale_id' => $saleId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function batchAction(Request $request, $batchId)
    {
        $batch = ProductBatch::with('product')->findOrFail($batchId);
        $action = $request->input('action');

        try {
            switch ($action) {
                case 'mark_expired':
                    if ($batch->quantity_remaining > 0) {
                        DB::beginTransaction();

                        $lossValue = $batch->quantity_remaining * $batch->unit_cost;

                        StockMovement::createExpiredMovement(
                            $batch->product_id,
                            $batch->quantity_remaining,
                            "Manually marked as expired - Batch: {$batch->batch_number} (Loss: ₱" . number_format($lossValue, 2) . ")",
                            $batch->id
                        );

                        $batch->update(['quantity_remaining' => 0]);
                        $batch->product->updateStockQuantity();

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => "Batch {$batch->batch_number} marked as expired (Loss: ₱" . number_format($lossValue, 2) . ")"
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Batch is already empty'
                        ], 400);
                    }

                case 'adjust_quantity':
                    $newQuantity = (int)$request->input('new_quantity');
                    $reason = $request->input('reason', 'Manual adjustment');

                    if ($newQuantity < 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Quantity cannot be negative'
                        ], 400);
                    }

                    DB::beginTransaction();

                    $difference = $newQuantity - $batch->quantity_remaining;

                    if ($difference !== 0) {
                        StockMovement::createAdjustmentMovement(
                            $batch->product_id,
                            $difference,
                            "Batch quantity adjustment - Batch: {$batch->batch_number} - {$reason}",
                            $batch->id
                        );

                        $batch->update(['quantity_remaining' => $newQuantity]);
                        $batch->product->updateStockQuantity();
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => "Batch {$batch->batch_number} quantity adjusted to {$newQuantity}"
                    ]);

                case 'update_pricing':
                    $newUnitCost = $request->input('unit_cost');
                    $newSalePrice = $request->input('sale_price');
                    $reason = $request->input('reason', 'Price update');

                    if ($newUnitCost !== null && $newUnitCost < 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unit cost cannot be negative'
                        ], 400);
                    }

                    if ($newSalePrice !== null && $newSalePrice < 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sale price cannot be negative'
                        ], 400);
                    }

                    DB::beginTransaction();

                    $batch->updatePricing($newUnitCost, $newSalePrice, $reason);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => "Batch {$batch->batch_number} pricing updated successfully"
                    ]);

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid action'
                    ], 400);
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollback();
            }

            Log::error('Batch action failed', [
                'batch_id' => $batchId,
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function batchHistory($batchId)
    {
        $batch = ProductBatch::with(['product', 'supplier'])->findOrFail($batchId);

        $movements = StockMovement::where('product_id', $batch->product_id)
            ->where('notes', 'LIKE', '%Batch: ' . $batch->batch_number . '%')
            ->orderBy('created_at', 'desc')
            ->with('product')
            ->get();

        return view('products.batch-history', compact('batch', 'movements'));
    }

    public function stockMovements($productId, Request $request)
    {
        $product = Product::findOrFail($productId);

        $query = StockMovement::where('product_id', $productId)
            ->with('product')
            ->orderBy('created_at', 'desc');

        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(50);
        $movementTypes = StockMovement::getMovementTypes();

        return view('products.stock-movements', compact('product', 'movements', 'movementTypes'));
    }

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
    public function showBatches($productId)
    {
        $product = Product::with(['batches' => function ($query) {
            $query->orderBy('expiration_date', 'asc');
        }, 'supplier'])->findOrFail($productId);

        return view('inventory.batches-modal', compact('product'));
    }
    public function onlyshowBatches($productId)
    {
        $product = Product::with(['batches' => function ($query) {
            $query->orderBy('expiration_date', 'asc');
        }, 'supplier'])->findOrFail($productId);

        return view('products.batches-modal', compact('product'));
    }
}

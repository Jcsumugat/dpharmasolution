<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    // Show the inventory with search & sort
    public function index(Request $request)
    {
        $query = Product::with(['batches' => function ($query) {
            $query->where('quantity_remaining', '>', 0)
                ->orderBy('expiration_date', 'asc');
        }, 'supplier']);

        // Add batch count and totals
        $query->withCount('batches')
            ->addSelect([
                'total_stock' => ProductBatch::selectRaw('COALESCE(SUM(quantity_remaining), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->where('expiration_date', '>', now()),
                'earliest_expiration' => ProductBatch::select('expiration_date')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->orderBy('expiration_date', 'asc')
                    ->limit(1),
                'latest_expiration' => ProductBatch::select('expiration_date')
                    ->whereColumn('product_id', 'products.id')
                    ->where('quantity_remaining', '>', 0)
                    ->orderBy('expiration_date', 'desc')
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
                    $query->orderBy('sale_price', $direction);
                    break;
                case 'quantity':
                    $query->orderBy('total_stock', $direction);
                    break;
                case 'expiry':
                    $query->orderBy('earliest_expiration', $direction);
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

    // Add new batch to product
    public function addBatch(Request $request, Product $product)
    {
        // Fixed validation to match form field names
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

            // Create new batch
            $batch = ProductBatch::create([
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
            ]);

            // Update product's sale price if provided and different
            if ($request->filled('sale_price') && $request->sale_price != $product->sale_price) {
                $product->update(['sale_price' => $request->sale_price]);
            }

            // Update total stock quantity
            $product->updateStockQuantity();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "New batch added successfully! Batch #{$batch->batch_number} with {$request->quantity_received} units."
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

    // View batches for a product
    public function viewBatches(Product $product)
    {
        $batches = $product->batches()
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->get();

        return view('admin.partials.product-batches', compact('product', 'batches'));
    }

    // Stock Out (Enhanced version with batch support)
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

                // Validate quantity
                if ($quantity > $batch->quantity_remaining) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove {$quantity} units. Only {$batch->quantity_remaining} units available in this batch."
                    ], 400);
                }

                // Use the batch's built-in method to reduce quantity
                $batch->reduceQuantity(
                    $quantity,
                    \App\Models\StockMovement::TYPE_SALE,
                    $this->getReasonText($request->reason) . ($request->notes ? ' - ' . $request->notes : ''),
                    \App\Models\StockMovement::REFERENCE_MANUAL,
                    null
                );

                $message = "Successfully removed {$quantity} units from batch {$batch->batch_number}.";
            } else {
                // Original stock out logic for backward compatibility
                if ($quantity > $product->total_stock) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot remove {$quantity} from {$product->product_name}, not enough stock."
                    ], 400);
                }

                // Use FIFO to remove from batches
                $batchAllocation = $product->getBatchesForQuantity($quantity);

                if (!$batchAllocation['can_fulfill']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot fulfill request. Available: {$batchAllocation['available']}, Requested: {$quantity}"
                    ], 400);
                }

                foreach ($batchAllocation['batches'] as $allocation) {
                    $allocation['batch']->reduceQuantity(
                        $allocation['quantity'],
                        \App\Models\StockMovement::TYPE_SALE,
                        $this->getReasonText($request->reason) . ($request->notes ? ' - ' . $request->notes : ''),
                        \App\Models\StockMovement::REFERENCE_MANUAL,
                        null
                    );
                }

                $message = "Removed {$quantity} from {$product->product_name}.";
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
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

    // Stock In (Enhanced version)
    public function stockIn(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock_in' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $qty = $request->stock_in;

        $product->stock_quantity += $qty;
        $product->save();

        return redirect()->route('inventory.index')
            ->with('stock_action', "Added {$qty} to {$product->product_name}.")
            ->with('stock_in_product', $product->id)
            ->with('stock_in_qty', $qty);
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
}
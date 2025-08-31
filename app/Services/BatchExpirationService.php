<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchExpirationService
{
    /**
     * Get all products with only non-expired stock for POS/Sales
     */
    public static function getAvailableProductsForSale()
    {
        return Product::with(['availableBatches' => function ($query) {
                $query->orderBy('expiration_date', 'asc')
                      ->orderBy('received_date', 'asc');
            }])
            ->whereHas('batches', function ($q) {
                $q->where('quantity_remaining', '>', 0)
                  ->where('expiration_date', '>', now());
            })
            ->get()
            ->map(function ($product) {
                $product->setAttribute('current_price', $product->current_sale_price);
                $product->setAttribute('available_quantity', $product->available_stock);
                return $product;
            });
    }

    /**
     * Check stock availability for multiple products (excluding expired)
     */
    public static function checkStockAvailability(array $items)
    {
        $results = [];
        $allAvailable = true;

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $requestedQty = $item['quantity'];

            $product = Product::find($productId);
            if (!$product) {
                $results[] = [
                    'product_id' => $productId,
                    'available' => false,
                    'reason' => 'Product not found',
                    'available_quantity' => 0,
                    'requested_quantity' => $requestedQty
                ];
                $allAvailable = false;
                continue;
            }

            $availableStock = $product->available_stock;
            $canFulfill = $availableStock >= $requestedQty;

            if (!$canFulfill) {
                $allAvailable = false;
            }

            $results[] = [
                'product_id' => $productId,
                'product_name' => $product->product_name,
                'available' => $canFulfill,
                'reason' => $canFulfill ? 'Available' : 'Insufficient non-expired stock',
                'available_quantity' => $availableStock,
                'requested_quantity' => $requestedQty,
                'shortage' => $canFulfill ? 0 : ($requestedQty - $availableStock),
                'expired_stock' => $product->getExpiredQuantity()
            ];
        }

        return [
            'all_available' => $allAvailable,
            'items' => $results
        ];
    }

    /**
     * Get FIFO batch allocation for a sale (excluding expired batches)
     */
    public static function allocateBatchesForSale($productId, $quantity)
    {
        $batches = ProductBatch::where('product_id', $productId)
            ->available() // This scope excludes expired batches
            ->fifo() // Orders by expiration date, then received date
            ->get();

        $allocation = [];
        $remainingQuantity = $quantity;
        $totalCost = 0;
        $totalRevenue = 0;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $allocateFromBatch = min($remainingQuantity, $batch->quantity_remaining);

            $allocation[] = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'quantity' => $allocateFromBatch,
                'unit_cost' => $batch->unit_cost,
                'sale_price' => $batch->sale_price,
                'expiration_date' => $batch->expiration_date,
                'subtotal_cost' => $allocateFromBatch * $batch->unit_cost,
                'subtotal_revenue' => $allocateFromBatch * $batch->sale_price
            ];

            $totalCost += $allocateFromBatch * $batch->unit_cost;
            $totalRevenue += $allocateFromBatch * $batch->sale_price;
            $remainingQuantity -= $allocateFromBatch;
        }

        $canFulfill = $remainingQuantity <= 0;

        return [
            'can_fulfill' => $canFulfill,
            'allocated_quantity' => $quantity - $remainingQuantity,
            'remaining_quantity' => $remainingQuantity,
            'allocation' => $allocation,
            'total_cost' => $totalCost,
            'total_revenue' => $totalRevenue,
            'profit' => $totalRevenue - $totalCost
        ];
    }

    /**
     * Process a sale by deducting from batches using FIFO
     */
    public static function processSale($productId, $quantity, $saleId, $notes = null)
    {
        try {
            DB::beginTransaction();

            $allocation = self::allocateBatchesForSale($productId, $quantity);

            if (!$allocation['can_fulfill']) {
                throw new \Exception("Cannot fulfill sale - insufficient non-expired stock");
            }

            $deductionLog = [];

            foreach ($allocation['allocation'] as $batchAllocation) {
                $batch = ProductBatch::find($batchAllocation['batch_id']);

                if (!$batch || $batch->isExpired()) {
                    throw new \Exception("Batch {$batchAllocation['batch_number']} is expired or not found");
                }

                $batch->reduceQuantity(
                    $batchAllocation['quantity'],
                    StockMovement::TYPE_SALE,
                    $notes,
                    'sale',
                    $saleId
                );

                $deductionLog[] = [
                    'batch_number' => $batch->batch_number,
                    'quantity_deducted' => $batchAllocation['quantity'],
                    'remaining_in_batch' => $batch->quantity_remaining
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'deduction_log' => $deductionLog,
                'total_cost' => $allocation['total_cost'],
                'total_revenue' => $allocation['total_revenue'],
                'profit' => $allocation['profit']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing sale for product {$productId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get products with expired stock for cleanup
     */
    public static function getProductsWithExpiredStock()
    {
        return Product::whereHas('expiredBatches')
            ->with(['expiredBatches' => function ($query) {
                $query->orderBy('expiration_date');
            }])
            ->get()
            ->map(function ($product) {
                $expiredBatches = $product->expiredBatches;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'expired_batches_count' => $expiredBatches->count(),
                    'total_expired_quantity' => $expiredBatches->sum('quantity_remaining'),
                    'total_expired_value' => $expiredBatches->sum(function ($batch) {
                        return $batch->quantity_remaining * $batch->unit_cost;
                    }),
                    'oldest_expired_date' => $expiredBatches->min('expiration_date'),
                    'newest_expired_date' => $expiredBatches->max('expiration_date')
                ];
            });
    }

    /**
     * Get dashboard statistics excluding expired stock
     */
    public static function getInventoryStats()
    {
        $now = now();

        return [
            // Available inventory (non-expired)
            'total_available_products' => Product::inStock()->count(),
            'total_available_quantity' => ProductBatch::available()->sum('quantity_remaining'),
            'total_available_value' => ProductBatch::available()
                ->selectRaw('SUM(unit_cost * quantity_remaining)')
                ->value('SUM(unit_cost * quantity_remaining)') ?: 0,

            // Expired inventory
            'expired_products_count' => Product::whereHas('expiredBatches')->count(),
            'total_expired_quantity' => ProductBatch::expired()->sum('quantity_remaining'),
            'total_expired_value' => ProductBatch::expired()
                ->selectRaw('SUM(unit_cost * quantity_remaining)')
                ->value('SUM(unit_cost * quantity_remaining)') ?: 0,

            // Expiring soon (next 30 days)
            'expiring_soon_count' => ProductBatch::expiringSoon(30)->inStock()->count(),
            'expiring_soon_quantity' => ProductBatch::expiringSoon(30)->inStock()->sum('quantity_remaining'),

            // Low stock (based on non-expired inventory)
            'low_stock_products' => Product::lowStock()->count(),

            // Out of stock (no non-expired inventory)
            'out_of_stock_products' => Product::whereDoesntHave('availableBatches')->count(),
        ];
    }

    /**
     * Generate expiration report
     */
    public static function generateExpirationReport($days = 90)
    {
        $now = now();

        return [
            'report_date' => $now->format('Y-m-d H:i:s'),
            'period_days' => $days,

            // Expired batches
            'expired' => [
                'batches' => ProductBatch::expired()->inStock()->with('product')->get(),
                'total_quantity' => ProductBatch::expired()->sum('quantity_remaining'),
                'total_value' => ProductBatch::expired()
                    ->selectRaw('SUM(unit_cost * quantity_remaining)')
                    ->value('SUM(unit_cost * quantity_remaining)') ?: 0
            ],

            // Expiring in next 7 days
            'critical' => [
                'batches' => ProductBatch::expiringSoon(7)->inStock()->with('product')->get(),
                'total_quantity' => ProductBatch::expiringSoon(7)->inStock()->sum('quantity_remaining'),
            ],

            // Expiring in 8-30 days
            'warning' => [
                'batches' => ProductBatch::where('expiration_date', '>', now()->addDays(7))
                    ->where('expiration_date', '<=', now()->addDays(30))
                    ->inStock()
                    ->with('product')
                    ->get(),
                'total_quantity' => ProductBatch::where('expiration_date', '>', now()->addDays(7))
                    ->where('expiration_date', '<=', now()->addDays(30))
                    ->inStock()
                    ->sum('quantity_remaining'),
            ],

            // All expiring within the specified period
            'all_expiring' => ProductBatch::expiringSoon($days)
                ->inStock()
                ->with('product')
                ->orderBy('expiration_date')
                ->get()
        ];
    }

    /**
     * Clean up expired batches (mark them as expired in stock movements)
     */
    public static function cleanupExpiredBatches($autoMarkExpired = false)
    {
        $expiredBatches = ProductBatch::expired()
            ->where('quantity_remaining', '>', 0)
            ->with('product')
            ->get();

        $cleanupResults = [];

        foreach ($expiredBatches as $batch) {
            $result = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_name' => $batch->product->product_name,
                'expired_quantity' => $batch->quantity_remaining,
                'expiration_date' => $batch->expiration_date->format('Y-m-d'),
                'days_expired' => abs($batch->daysUntilExpiration())
            ];

            if ($autoMarkExpired) {
                try {
                    $batch->markAsExpired('Auto-cleanup of expired batch');
                    $result['action'] = 'marked_expired';
                    $result['success'] = true;
                } catch (\Exception $e) {
                    $result['action'] = 'failed';
                    $result['success'] = false;
                    $result['error'] = $e->getMessage();
                }
            } else {
                $result['action'] = 'identified_only';
                $result['success'] = true;
            }

            $cleanupResults[] = $result;
        }

        return [
            'total_expired_batches' => count($cleanupResults),
            'total_expired_quantity' => collect($cleanupResults)->sum('expired_quantity'),
            'cleanup_performed' => $autoMarkExpired,
            'results' => $cleanupResults
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'product_name',
        'manufacturer',
        'product_type',
        'form_type',
        'dosage_unit',
        'unit',
        'unit_quantity',
        'classification',
        'storage_requirements',
        'reorder_level',
        'category_id',
        'supplier_id',
        'brand_name',
        'notification_sent_at',
    ];

    // Classification constants and helper methods
    const CLASSIFICATIONS = [
        1 => 'Antibiotic',
        2 => 'Analgesic',
        3 => 'Antipyretic',
        4 => 'Anti-inflammatory',
        5 => 'Antacid',
        6 => 'Antihistamine',
        7 => 'Antihypertensive',
        8 => 'Antidiabetic',
        9 => 'Vitamin',
        10 => 'Mineral',
        11 => 'Supplement',
        12 => 'Topical',
        13 => 'Other',
    ];

    public function getClassificationNameAttribute()
    {
        return self::CLASSIFICATIONS[$this->classification] ?? 'Unknown';
    }

    public static function getClassificationOptions()
    {
        return self::CLASSIFICATIONS;
    }

    protected $casts = [
        'reorder_level' => 'integer',
        'notification_sent_at' => 'datetime',
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    // Only non-expired batches with stock
    public function availableBatches()
    {
        return $this->hasMany(ProductBatch::class)
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '>', now());
    }

    // Expired batches (for management purposes)
    public function expiredBatches()
    {
        return $this->hasMany(ProductBatch::class)
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '<=', now());
    }

    public function prescriptionItems()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    // UPDATED: Only count non-expired stock
    public function getTotalStockAttribute()
    {
        if ($this->relationLoaded('batches')) {
            return $this->batches
                ->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now())
                ->sum('quantity_remaining');
        }

        return $this->batches()
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '>', now())
            ->sum('quantity_remaining');
    }

    // UPDATED: Get current stock quantity (from database if column exists, otherwise calculate)
    public function getStockQuantityAttribute()
    {
        // If stock_quantity column exists and is set, use it
        if (isset($this->attributes['stock_quantity'])) {
            return (int) $this->attributes['stock_quantity'];
        }

        // Otherwise calculate from available batches
        return $this->available_stock;
    }

    // UPDATED: Get available stock (excluding expired)
    public function getAvailableStockAttribute()
    {
        return $this->availableBatches()->sum('quantity_remaining');
    }

    public function getEarliestExpirationAttribute()
    {
        return $this->availableBatches()
            ->orderBy('expiration_date')
            ->first()?->expiration_date;
    }

    public function getLatestExpirationAttribute()
    {
        return $this->availableBatches()
            ->orderBy('expiration_date', 'desc')
            ->first()?->expiration_date;
    }

    // UPDATED: Only calculate value from non-expired batches
    public function getTotalValueAttribute()
    {
        return $this->availableBatches()
            ->selectRaw('SUM(unit_cost * quantity_remaining)')
            ->value('SUM(unit_cost * quantity_remaining)') ?: 0;
    }

    // UPDATED: Get price from FIFO non-expired batch
    public function getCurrentSalePriceAttribute()
    {
        // Get the sale price from the batch that would be sold first (FIFO) - excluding expired
        return $this->availableBatches()
            ->orderBy('expiration_date')
            ->orderBy('received_date')
            ->first()?->sale_price;
    }

    // UPDATED: Get unit price from FIFO non-expired batch
    public function getUnitPriceAttribute()
    {
        // Get price from the earliest expiring non-expired batch (FIFO)
        $batch = $this->batches()
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '>', now())
            ->orderBy('expiration_date', 'asc')
            ->orderBy('received_date', 'asc')
            ->first();

        return $batch ? $batch->sale_price : 0;
    }

    // Scopes for filtering products
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereHas('batches', function ($q) use ($days) {
            $q->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '<=', now()->addDays($days))
                ->where('expiration_date', '>', now());
        });
    }

    // UPDATED: Only products with non-expired stock
    public function scopeInStock($query)
    {
        return $query->whereHas('batches', function ($q) {
            $q->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        });
    }

    // UPDATED: Low stock based on non-expired inventory
    public function scopeLowStock($query, $threshold = null)
    {
        return $query->whereHas('batches', function ($q) use ($threshold) {
            $q->where('quantity_remaining', '>', 0)
                ->where('expiration_date', '>', now());
        })->where(function ($query) use ($threshold) {
            $query->whereRaw('(
                SELECT COALESCE(SUM(quantity_remaining), 0)
                FROM product_batches
                WHERE product_batches.product_id = products.id
                AND quantity_remaining > 0
                AND expiration_date > NOW()
            ) <= COALESCE(?, products.reorder_level, 10)', [$threshold]);
        });
    }

    // Products with near expiry batches
    public function scopeNearExpiry($query, $days = 30)
    {
        return $query->whereHas('batches', function ($q) use ($days) {
            $q->where('expiration_date', '<=', now()->addDays($days))
                ->where('expiration_date', '>', now())
                ->where('quantity_remaining', '>', 0);
        });
    }

    // Helper Methods
    public function isLowStock()
    {
        return $this->available_stock <= $this->reorder_level;
    }

    public function hasExpiredBatches()
    {
        return $this->expiredBatches()->exists();
    }

    public function getExpiredBatchesCount()
    {
        return $this->expiredBatches()->count();
    }

    public function getExpiredQuantity()
    {
        return $this->expiredBatches()->sum('quantity_remaining');
    }

    /**
     * UPDATED: Get batches for a specific quantity using FIFO (excluding expired batches)
     */
    public function getBatchesForQuantity($requestedQuantity)
    {
        $batches = $this->availableBatches()
            ->orderBy('expiration_date')
            ->orderBy('received_date')
            ->get();

        $allocation = [];
        $remainingQuantity = $requestedQuantity;
        $totalAvailable = $this->available_stock; // Uses non-expired stock only

        if ($totalAvailable < $requestedQuantity) {
            return [
                'can_fulfill' => false,
                'shortage' => $requestedQuantity - $totalAvailable,
                'available' => $totalAvailable,
                'batches' => [],
                'expired_stock_note' => $this->hasExpiredBatches() ?
                    "Note: {$this->getExpiredQuantity()} units available in expired batches" : null
            ];
        }

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $quantityFromBatch = min($remainingQuantity, $batch->quantity_remaining);

            $allocation[] = [
                'batch' => $batch,
                'quantity' => $quantityFromBatch,
                'unit_cost' => $batch->unit_cost,
                'sale_price' => $batch->sale_price,
                'expiration_date' => $batch->expiration_date
            ];

            $remainingQuantity -= $quantityFromBatch;
        }

        return [
            'can_fulfill' => true,
            'shortage' => 0,
            'batches' => $allocation,
            'total_cost' => collect($allocation)->sum(function ($item) {
                return $item['quantity'] * $item['unit_cost'];
            }),
            'total_revenue' => collect($allocation)->sum(function ($item) {
                return $item['quantity'] * $item['sale_price'];
            })
        ];
    }

    /**
     * UPDATED: Update cached fields including stock_quantity (if column exists)
     */
    public function updateCachedFields()
    {
        $availableBatches = $this->availableBatches()->get();

        $totalQuantity = $availableBatches->sum('quantity_remaining');
        $totalCostValue = $availableBatches->sum(function ($batch) {
            return $batch->unit_cost * $batch->quantity_remaining;
        });
        $totalSaleValue = $availableBatches->sum(function ($batch) {
            return $batch->sale_price * $batch->quantity_remaining;
        });

        $updates = [];

        // Only update stock_quantity if the column exists
        if (Schema::hasColumn('products', 'stock_quantity')) {
            $updates['stock_quantity'] = $totalQuantity;
        }

        // Add other calculated fields if columns exist
        if (Schema::hasColumn('products', 'average_unit_cost')) {
            $updates['average_unit_cost'] = $totalQuantity > 0 ? $totalCostValue / $totalQuantity : 0;
        }

        if (Schema::hasColumn('products', 'average_sale_price')) {
            $updates['average_sale_price'] = $totalQuantity > 0 ? $totalSaleValue / $totalQuantity : 0;
        }

        if (Schema::hasColumn('products', 'lowest_sale_price')) {
            $updates['lowest_sale_price'] = $availableBatches->min('sale_price');
        }

        if (Schema::hasColumn('products', 'highest_sale_price')) {
            $updates['highest_sale_price'] = $availableBatches->max('sale_price');
        }

        if (!empty($updates)) {
            $this->update($updates);
        }
    }

    /**
     * Backwards compatibility method
     */
    public function updateStockQuantity()
    {
        $this->updateCachedFields();
    }

    /**
     * UPDATED: Get batch summary for display (excluding expired)
     */
    public function getBatchSummary()
    {
        $availableBatches = $this->availableBatches()->get();
        $expiredBatches = $this->expiredBatches()->get();

        return [
            'total_available_batches' => $availableBatches->count(),
            'total_expired_batches' => $expiredBatches->count(),
            'total_quantity' => $this->available_stock,
            'expired_quantity' => $expiredBatches->sum('quantity_remaining'),
            'earliest_expiry' => $this->earliest_expiration,
            'latest_expiry' => $this->latest_expiration,
            'expiring_soon' => $availableBatches->where('expiration_date', '<=', now()->addDays(30))->count(),
            'average_unit_cost' => $availableBatches->isNotEmpty() ?
                $availableBatches->avg('unit_cost') : 0,
            'average_sale_price' => $availableBatches->isNotEmpty() ?
                $availableBatches->avg('sale_price') : 0,
            'price_range' => [
                'min_sale_price' => $availableBatches->min('sale_price'),
                'max_sale_price' => $availableBatches->max('sale_price'),
            ]
        ];
    }

    public function posTransactionItems()
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    // Add this method to get total sales for a product
    public function getTotalSalesAttribute()
    {
        return $this->posTransactionItems()
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'completed');
            })
            ->sum('total_price');
    }

    // Add this method to get total quantity sold
    public function getTotalQuantitySoldAttribute()
    {
        return $this->posTransactionItems()
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'completed');
            })
            ->sum('quantity');
    }

    /**
     * UPDATED: Check if product can fulfill quantity from non-expired stock
     */
    public function canFulfillQuantity($quantity)
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Get stock status with expiration information
     */
    public function getStockStatus()
    {
        $availableStock = $this->available_stock;
        $expiredStock = $this->getExpiredQuantity();
        $totalStock = $availableStock + $expiredStock;

        return [
            'available_stock' => $availableStock,
            'expired_stock' => $expiredStock,
            'total_stock' => $totalStock,
            'is_low_stock' => $this->isLowStock(),
            'has_expired_batches' => $this->hasExpiredBatches(),
            'stock_status' => $this->getStockStatusMessage()
        ];
    }

    /**
     * Get human-readable stock status message
     */
    public function getStockStatusMessage()
    {
        $availableStock = $this->available_stock;
        $expiredStock = $this->getExpiredQuantity();

        if ($availableStock <= 0 && $expiredStock > 0) {
            return "Out of stock - {$expiredStock} units expired";
        } elseif ($availableStock <= 0) {
            return "Out of stock";
        } elseif ($this->isLowStock()) {
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

    /**
     * Check if product is available for sale (has non-expired stock)
     */
    public function isAvailableForSale()
    {
        return $this->available_stock > 0;
    }

    /**
     * Get next batch to expire from available stock
     */
    public function getNextExpiringBatch()
    {
        return $this->availableBatches()
            ->orderBy('expiration_date')
            ->orderBy('received_date')
            ->first();
    }

    /**
     * Get batches expiring within specified days
     */
    public function getBatchesExpiringSoon($days = 30)
    {
        return $this->availableBatches()
            ->where('expiration_date', '<=', now()->addDays($days))
            ->orderBy('expiration_date')
            ->get();
    }
    /**
     * Get display name for the unit type
     */
    public function getUnitDisplay()
    {
        $unitMap = [
            'bottle' => 'bottle',
            'ml' => 'mL',
            'L' => 'L',
            'vial' => 'vial',
            'ampoule' => 'ampoule',
            'dropper_bottle' => 'dropper bottle',
            'nebule' => 'nebule',
            'tablet' => 'tablet',
            'capsule' => 'capsule',
            'blister_pack' => 'blister pack',
            'box' => 'box',
            'strip' => 'strip',
            'sachet' => 'sachet',
            'syringe' => 'syringe',
            'injection_vial' => 'vial',
            'injection_ampoule' => 'ampoule',
            'tube' => 'tube',
            'jar' => 'jar',
            'topical_bottle' => 'bottle',
            'inhaler' => 'inhaler',
            'patch' => 'patch',
            'suppository' => 'suppository',
            'piece' => 'pcs',
            'pack' => 'pack',
        ];

        $display = $unitMap[$this->unit] ?? $this->unit;

        // For volume units, show the quantity
        $volumeUnits = [
            'bottle',
            'ml',
            'L',
            'vial',
            'ampoule',
            'dropper_bottle',
            'nebule',
            'tube',
            'jar',
            'topical_bottle',
            'syringe',
            'injection_vial',
            'injection_ampoule'
        ];

        if (in_array($this->unit, $volumeUnits) && $this->unit_quantity && $this->unit_quantity != 1) {
            if ($this->unit === 'ml' || $this->unit === 'L') {
                return $display;
            }
            return $display . ' (' . number_format($this->unit_quantity, 0) . 'mL)';
        }

        // For packs, show items per pack
        if (in_array($this->unit, ['blister_pack', 'strip', 'box', 'pack']) && $this->unit_quantity && $this->unit_quantity != 1) {
            return $display . ' (' . number_format($this->unit_quantity, 0) . ' pcs)';
        }

        return $display;
    }

    /**
     * Get full unit description for detailed display
     */
    public function getFullUnitDescription()
    {
        if (!$this->unit || $this->unit === 'piece') {
            return 'per piece';
        }

        return 'per ' . $this->getUnitDisplay();
    }
}

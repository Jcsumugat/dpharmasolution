<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'stock_quantity' => 'integer',
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

    public function availableBatches()
    {
        return $this->hasMany(ProductBatch::class)
            ->where('quantity_remaining', '>', 0)
            ->where('expiration_date', '>', now());
    }

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

    // Optimized accessors using cached fields where possible
    public function getTotalStockAttribute()
    {
        return $this->stock_quantity ?? 0;
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

    public function getTotalValueAttribute()
    {
        return $this->availableBatches()
            ->selectRaw('SUM(unit_cost * quantity_remaining)')
            ->value('SUM(unit_cost * quantity_remaining)') ?: 0;
    }

    public function getCurrentSalePriceAttribute()
    {
        // Get the sale price from the batch that would be sold first (FIFO)
        return $this->availableBatches()
            ->orderBy('expiration_date')
            ->orderBy('received_date')
            ->first()?->sale_price;
    }

    // Scopes
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= reorder_level');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereHas('batches', function ($q) use ($days) {
            $q->where('quantity_remaining', '>', 0)
              ->where('expiration_date', '<=', now()->addDays($days))
              ->where('expiration_date', '>', now());
        });
    }

    // Helper Methods
    public function isLowStock()
    {
        return $this->total_stock <= $this->reorder_level;
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
     * Get batches for a specific quantity using FIFO (First In, First Out)
     */
    public function getBatchesForQuantity($requestedQuantity)
    {
        $batches = $this->availableBatches()
            ->orderBy('expiration_date')
            ->orderBy('received_date')
            ->get();

        $allocation = [];
        $remainingQuantity = $requestedQuantity;
        $totalAvailable = $this->stock_quantity;

        if ($totalAvailable < $requestedQuantity) {
            return [
                'can_fulfill' => false,
                'shortage' => $requestedQuantity - $totalAvailable,
                'available' => $totalAvailable,
                'batches' => []
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
            'total_cost' => collect($allocation)->sum(function($item) {
                return $item['quantity'] * $item['unit_cost'];
            }),
            'total_revenue' => collect($allocation)->sum(function($item) {
                return $item['quantity'] * $item['sale_price'];
            })
        ];
    }

    /**
     * Update all cached fields from batches (call this after batch changes)
     */
    public function updateCachedFields()
    {
        $availableBatches = $this->availableBatches()->get();
        
        $totalQuantity = $availableBatches->sum('quantity_remaining');
        $totalCostValue = $availableBatches->sum(function($batch) {
            return $batch->unit_cost * $batch->quantity_remaining;
        });
        $totalSaleValue = $availableBatches->sum(function($batch) {
            return $batch->sale_price * $batch->quantity_remaining;
        });

        $this->update([
            'stock_quantity' => $totalQuantity,
            'average_unit_cost' => $totalQuantity > 0 ? $totalCostValue / $totalQuantity : 0,
            'average_sale_price' => $totalQuantity > 0 ? $totalSaleValue / $totalQuantity : 0,
            'lowest_sale_price' => $availableBatches->min('sale_price'),
            'highest_sale_price' => $availableBatches->max('sale_price'),
        ]);
    }

    /**
     * Backwards compatibility method
     */
    public function updateStockQuantity()
    {
        $this->updateCachedFields();
    }

    /**
     * Get batch summary for display
     */
    public function getBatchSummary()
    {
        $batches = $this->availableBatches()->get();
        
        return [
            'total_batches' => $batches->count(),
            'total_quantity' => $this->stock_quantity,
            'earliest_expiry' => $this->earliest_expiration,
            'latest_expiry' => $this->latest_expiration,
            'expiring_soon' => $batches->where('expiration_date', '<=', now()->addDays(30))->count(),
            'average_unit_cost' => $this->average_unit_cost,
            'average_sale_price' => $this->average_sale_price,
            'price_range' => [
                'min_sale_price' => $this->lowest_sale_price,
                'max_sale_price' => $this->highest_sale_price,
            ]
        ];
    }
}
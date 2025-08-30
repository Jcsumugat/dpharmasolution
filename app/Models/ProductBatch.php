<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'batch_number',
        'expiration_date',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'sale_price',
        'received_date',
        'supplier_id',
        'notes',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'received_date' => 'date',
        'quantity_received' => 'integer',
        'quantity_remaining' => 'integer',
        'unit_cost' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Get stock movements related to this batch
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id')
                    ->where('notes', 'LIKE', '%Batch: ' . $this->batch_number . '%');
    }

    // Scopes
    public function scopeInStock($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expiration_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expiration_date', '<=', now());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiration_date', '<=', now()->addDays($days))
                    ->where('expiration_date', '>', now());
    }

    public function scopeAvailable($query)
    {
        return $query->inStock()->notExpired();
    }

    // Calculated Properties
    public function getQuantitySoldAttribute()
    {
        return $this->quantity_received - $this->quantity_remaining;
    }

    public function getTotalCostValueAttribute()
    {
        return $this->quantity_remaining * $this->unit_cost;
    }

    public function getTotalSaleValueAttribute()
    {
        return $this->quantity_remaining * $this->sale_price;
    }

    public function getPotentialProfitAttribute()
    {
        return $this->total_sale_value - $this->total_cost_value;
    }

    public function getProfitMarginAttribute()
    {
        if ($this->unit_cost == 0) return 0;
        return round((($this->sale_price - $this->unit_cost) / $this->unit_cost) * 100, 2);
    }

    public function getUsagePercentageAttribute()
    {
        if ($this->quantity_received == 0) return 0;
        return round(($this->quantity_sold / $this->quantity_received) * 100, 2);
    }

    // Helper Methods
    public function isExpired()
    {
        return $this->expiration_date <= now();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->expiration_date <= now()->addDays($days) && !$this->isExpired();
    }

    public function daysUntilExpiration()
    {
        return now()->diffInDays($this->expiration_date, false);
    }

    public function getExpirationStatus()
    {
        $daysUntilExpiry = $this->daysUntilExpiration();

        if ($daysUntilExpiry < 0) {
            return [
                'status' => 'expired',
                'class' => 'text-red-600',
                'message' => 'Expired ' . abs($daysUntilExpiry) . ' days ago'
            ];
        } elseif ($daysUntilExpiry <= 7) {
            return [
                'status' => 'critical',
                'class' => 'text-red-500',
                'message' => 'Expires in ' . $daysUntilExpiry . ' days'
            ];
        } elseif ($daysUntilExpiry <= 30) {
            return [
                'status' => 'warning',
                'class' => 'text-yellow-500',
                'message' => 'Expires in ' . $daysUntilExpiry . ' days'
            ];
        } else {
            return [
                'status' => 'good',
                'class' => 'text-green-600',
                'message' => 'Expires in ' . $daysUntilExpiry . ' days'
            ];
        }
    }

    public function canFulfillQuantity($quantity)
    {
        return $this->quantity_remaining >= $quantity && !$this->isExpired();
    }

    /**
     * Reduce quantity and create stock movement
     */
    public function reduceQuantity($quantity, $movementType = StockMovement::TYPE_SALE, $notes = null, $referenceType = null, $referenceId = null)
    {
        if ($this->quantity_remaining < $quantity) {
            throw new \Exception("Insufficient stock in batch {$this->batch_number}");
        }

        $this->decrement('quantity_remaining', $quantity);

        // Create stock movement record with batch info in notes
        $batchNotes = "Batch: {$this->batch_number}" . ($notes ? " - {$notes}" : "");

        StockMovement::createMovement(
            $this->product_id,
            $movementType,
            -$quantity, // Negative for stock out
            $referenceType,
            $referenceId,
            $batchNotes
        );

        // Update product stock quantity
        $this->product->updateStockQuantity();

        return $this;
    }

    /**
     * Add quantity and create stock movement
     */
    public function addQuantity($quantity, $movementType = StockMovement::TYPE_PURCHASE, $notes = null, $referenceType = null, $referenceId = null)
    {
        $this->increment('quantity_remaining', $quantity);
        $this->increment('quantity_received', $quantity);

        // Create stock movement record with batch info in notes
        $batchNotes = "Batch: {$this->batch_number}" . ($notes ? " - {$notes}" : "");

        StockMovement::createMovement(
            $this->product_id,
            $movementType,
            $quantity, // Positive for stock in
            $referenceType,
            $referenceId,
            $batchNotes
        );

        // Update product stock quantity
        $this->product->updateStockQuantity();

        return $this;
    }

    /**
     * Mark entire batch as expired
     */
    public function markAsExpired($notes = 'Batch expired')
    {
        if ($this->quantity_remaining > 0) {
            $batchNotes = "Batch: {$this->batch_number} - {$notes}";

            StockMovement::createMovement(
                $this->product_id,
                StockMovement::TYPE_EXPIRED,
                -$this->quantity_remaining, // Negative for expired stock
                StockMovement::REFERENCE_EXPIRY,
                null,
                $batchNotes
            );

            $this->update(['quantity_remaining' => 0]);
            $this->product->updateStockQuantity();
        }

        return $this;
    }

    /**
     * Adjust batch quantity (for manual corrections)
     */
    public function adjustQuantity($newQuantity, $notes = 'Manual adjustment')
    {
        $difference = $newQuantity - $this->quantity_remaining;

        if ($difference != 0) {
            $batchNotes = "Batch: {$this->batch_number} - {$notes}";

            StockMovement::createAdjustmentMovement(
                $this->product_id,
                $difference,
                $batchNotes
            );

            $this->update(['quantity_remaining' => $newQuantity]);
            $this->product->updateStockQuantity();
        }

        return $this;
    }

    /**
     * Update pricing for this batch
     */
    public function updatePricing($unitCost = null, $salePrice = null, $notes = 'Price update')
    {
        $updates = [];
        $changes = [];

        if ($unitCost !== null && $unitCost != $this->unit_cost) {
            $updates['unit_cost'] = $unitCost;
            $changes[] = "Unit cost: ₱{$this->unit_cost} → ₱{$unitCost}";
        }

        if ($salePrice !== null && $salePrice != $this->sale_price) {
            $updates['sale_price'] = $salePrice;
            $changes[] = "Sale price: ₱{$this->sale_price} → ₱{$salePrice}";
        }

        if (!empty($updates)) {
            $this->update($updates);

            $changeLog = implode(', ', $changes);
            $batchNotes = "Batch: {$this->batch_number} - {$notes} ({$changeLog})";

            // Log the price change as an adjustment movement with 0 quantity
            StockMovement::createAdjustmentMovement(
                $this->product_id,
                0, // No quantity change, just price update
                $batchNotes
            );
        }

        return $this;
    }

    /**
     * Get formatted batch information including pricing
     */
    public function getBatchInfo()
    {
        $expirationStatus = $this->getExpirationStatus();

        return [
            'batch_number' => $this->batch_number,
            'quantity_remaining' => $this->quantity_remaining,
            'expiration_date' => $this->expiration_date->format('Y-m-d'),
            'expiration_status' => $expirationStatus,
            'unit_cost' => $this->unit_cost,
            'sale_price' => $this->sale_price,
            'profit_margin' => $this->profit_margin,
            'total_cost_value' => $this->total_cost_value,
            'total_sale_value' => $this->total_sale_value,
            'potential_profit' => $this->potential_profit,
            'supplier_name' => $this->supplier?->name ?? 'Unknown',
        ];
    }

    /**
     * Get batch movements using StockMovement model
     */
    public function getBatchMovements()
    {
        return StockMovement::where('product_id', $this->product_id)
                          ->where('notes', 'LIKE', '%Batch: ' . $this->batch_number . '%')
                          ->orderBy('created_at', 'desc')
                          ->get();
    }

    /**
     * Create a new batch with initial stock movement
     */
    public static function createWithMovement($data, $referenceType = null, $referenceId = null)
    {
        $batch = self::create($data);

        // Create initial stock movement
        $notes = "Initial stock - Batch: {$batch->batch_number}";

        StockMovement::createMovement(
            $batch->product_id,
            StockMovement::TYPE_PURCHASE,
            $batch->quantity_received,
            $referenceType ?: StockMovement::REFERENCE_PURCHASE,
            $referenceId,
            $notes
        );

        return $batch;
    }
}

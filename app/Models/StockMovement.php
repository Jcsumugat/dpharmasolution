<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'batch_id',
    ];

    // Movement types
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_EXPIRED = 'expired';
    const TYPE_DAMAGE = 'damage';
    const TYPE_RETURN = 'return';
    const TYPE_TRANSFER = 'transfer';

    // Reference types
    const REFERENCE_PURCHASE = 'purchase';
    const REFERENCE_SALE = 'sale';
    const REFERENCE_MANUAL = 'manual';
    const REFERENCE_EXPIRY = 'expiry';
    const REFERENCE_ADJUSTMENT = 'adjustment';
    const REFERENCE_SYSTEM = 'system';

    protected $casts = [
        'quantity' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class);
    }

    // Scopes
    public function scopeStockIn($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeStockOut($query)
    {
        return $query->where('quantity', '<', 0);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Static helper methods
    public static function createMovement($productId, $type, $quantity, $referenceType = null, $referenceId = null, $notes = null, $batchId = null)
    {
        return self::create([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType ?: self::REFERENCE_MANUAL,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'batch_id' => $batchId,
        ]);
    }

    public static function createSaleMovement($productId, $quantity, $saleId = null, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_SALE,
            -abs($quantity), // Ensure negative for stock out
            self::REFERENCE_SALE,
            $saleId,
            $notes ?: 'Product sold',
            $batchId
        );
    }

    public static function createPurchaseMovement($productId, $quantity, $purchaseId = null, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_PURCHASE,
            abs($quantity), // Ensure positive for stock in
            self::REFERENCE_PURCHASE,
            $purchaseId,
            $notes ?: 'Product purchased',
            $batchId
        );
    }

    public static function createAdjustmentMovement($productId, $quantity, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_ADJUSTMENT,
            $quantity, // Can be positive or negative
            self::REFERENCE_ADJUSTMENT,
            null,
            $notes ?: 'Stock adjustment',
            $batchId
        );
    }

    public static function createExpiredMovement($productId, $quantity, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_EXPIRED,
            -abs($quantity), // Ensure negative for stock out
            self::REFERENCE_EXPIRY,
            null,
            $notes ?: 'Product expired',
            $batchId
        );
    }

    public static function createDamageMovement($productId, $quantity, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_DAMAGE,
            -abs($quantity), // Ensure negative for stock out
            self::REFERENCE_MANUAL,
            null,
            $notes ?: 'Product damaged',
            $batchId
        );
    }

    public static function createReturnMovement($productId, $quantity, $returnId = null, $notes = null, $batchId = null)
    {
        return self::createMovement(
            $productId,
            self::TYPE_RETURN,
            -abs($quantity), // Ensure negative for stock out
            self::REFERENCE_MANUAL,
            $returnId,
            $notes ?: 'Product returned',
            $batchId
        );
    }

    // Helper methods
    public function isStockIn()
    {
        return $this->quantity > 0;
    }

    public function isStockOut()
    {
        return $this->quantity < 0;
    }

    public function getAbsoluteQuantity()
    {
        return abs($this->quantity);
    }

    public function getFormattedQuantity()
    {
        return $this->isStockIn() 
            ? '+' . number_format($this->quantity) 
            : number_format($this->quantity);
    }

    public function getTypeDisplayName()
    {
        $types = [
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_RETURN => 'Return',
            self::TYPE_TRANSFER => 'Transfer',
        ];

        return $types[$this->type] ?? ucfirst($this->type);
    }

    public function getReferenceDisplayName()
    {
        $references = [
            self::REFERENCE_PURCHASE => 'Purchase Order',
            self::REFERENCE_SALE => 'Sale Transaction',
            self::REFERENCE_MANUAL => 'Manual Entry',
            self::REFERENCE_EXPIRY => 'Product Expiry',
            self::REFERENCE_ADJUSTMENT => 'Stock Adjustment',
            self::REFERENCE_SYSTEM => 'System Generated',
        ];

        return $references[$this->reference_type] ?? ucfirst($this->reference_type);
    }

    public function getMovementTypes()
    {
        return [
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_RETURN => 'Return',
            self::TYPE_TRANSFER => 'Transfer',
        ];
    }

    public static function getMovementTypeOptions()
    {
        return [
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_RETURN => 'Return',
            self::TYPE_TRANSFER => 'Transfer',
        ];
    }

    public function getStatusBadgeClass()
    {
        switch ($this->type) {
            case self::TYPE_PURCHASE:
                return 'badge-success';
            case self::TYPE_SALE:
                return 'badge-primary';
            case self::TYPE_EXPIRED:
            case self::TYPE_DAMAGE:
                return 'badge-danger';
            case self::TYPE_ADJUSTMENT:
                return 'badge-warning';
            case self::TYPE_RETURN:
                return 'badge-info';
            default:
                return 'badge-secondary';
        }
    }

    // Query helpers
    public static function getStockSummaryForProduct($productId, $startDate = null, $endDate = null)
    {
        $query = self::where('product_id', $productId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $movements = $query->get();

        return [
            'total_in' => $movements->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs($movements->where('quantity', '<', 0)->sum('quantity')),
            'net_movement' => $movements->sum('quantity'),
            'movement_count' => $movements->count(),
            'by_type' => $movements->groupBy('type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_quantity' => $group->sum('quantity'),
                    'net_quantity' => $group->sum('quantity')
                ];
            })
        ];
    }

    public static function getRecentMovements($limit = 50, $productId = null)
    {
        $query = self::with(['product', 'batch'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get();
    }

    public static function getMovementsByDateRange($startDate, $endDate, $productId = null)
    {
        $query = self::with(['product', 'batch'])
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get();
    }

    // Validation helpers
    public static function validateMovementData($data)
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:' . implode(',', array_keys(self::getMovementTypeOptions())),
            'quantity' => 'required|integer|not_in:0',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            'batch_id' => 'nullable|exists:product_batches,id',
        ];

        return $rules;
    }

    // Audit trail methods
    public function getAuditDescription()
    {
        $action = $this->isStockIn() ? 'added to' : 'removed from';
        $quantity = $this->getAbsoluteQuantity();
        
        return "{$quantity} units {$action} {$this->product->product_name} ({$this->getTypeDisplayName()})";
    }

    public function toAuditArray()
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->product_name ?? 'Unknown',
            'type' => $this->type,
            'type_display' => $this->getTypeDisplayName(),
            'quantity' => $this->quantity,
            'formatted_quantity' => $this->getFormattedQuantity(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'batch_id' => $this->batch_id,
            'batch_number' => $this->batch->batch_number ?? null,
            'created_at' => $this->created_at,
            'description' => $this->getAuditDescription()
        ];
    }
}
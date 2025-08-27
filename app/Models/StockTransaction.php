<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'transaction_type',
        'quantity',
        'previous_stock',
        'new_stock',
        'unit_cost',
        'batch_number',
        'expiration_date',
        'notes',
        'reference_number',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'expiration_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the stock transaction
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_code');
    }

    /**
     * Scope for stock in transactions
     */
    public function scopeStockIn($query)
    {
        return $query->where('transaction_type', 'stock_in');
    }

    /**
     * Scope for stock out transactions
     */
    public function scopeStockOut($query)
    {
        return $query->where('transaction_type', 'stock_out');
    }

    /**
     * Scope for adjustment transactions
     */
    public function scopeAdjustments($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    /**
     * Get formatted transaction type
     */
    public function getFormattedTransactionTypeAttribute()
    {
        return match($this->transaction_type) {
            'stock_in' => 'Stock In',
            'stock_out' => 'Stock Out',
            'adjustment' => 'Stock Adjustment',
            default => ucfirst(str_replace('_', ' ', $this->transaction_type))
        };
    }

    /**
     * Get transaction color for UI
     */
    public function getTransactionColorAttribute()
    {
        return match($this->transaction_type) {
            'stock_in' => 'green',
            'stock_out' => 'red',
            'adjustment' => 'blue',
            default => 'gray'
        };
    }
}
<?php
// app/Models/PosTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'customer_type',
        'customer_name',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'change_amount',
        'payment_method',
        'status',
        'notes',
        'processed_by'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PosTransactionItem::class, 'transaction_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->transaction_id) {
                $model->transaction_id = 'TXN-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}

// app/Models/PosTransactionItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'brand_name',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

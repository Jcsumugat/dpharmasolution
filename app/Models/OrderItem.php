<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'available', // This is the actual column in your table
    ];

    protected $casts = [
        'available' => 'boolean',
    ];

    // 🔗 Each item belongs to an order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔗 Optional: link to the product (if needed)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
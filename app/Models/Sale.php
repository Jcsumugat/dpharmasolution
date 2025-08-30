<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'order_id', // Add this if missing
        'customer_id',
        'total_amount',
        'total_items', // Add this if missing
        'sale_date',
        'status',
        'payment_method',
        'notes'
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    // Relationship with prescription
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    // Relationship with order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relationship with customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship with sale items
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Calculate total from sale items
    public function calculateTotal()
    {
        return $this->items()->sum(DB::raw('quantity * unit_price'));
    }
    
}

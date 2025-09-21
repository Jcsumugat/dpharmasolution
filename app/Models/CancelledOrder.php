<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancelledOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'order_id',
        'customer_id',
        'cancelled_by',
        'cancellation_reason',
        'additional_message',
        'cancelled_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the prescription that was cancelled
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the order that was cancelled
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the customer whose order was cancelled
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the admin who cancelled the order
     */
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get readable cancellation reason
     */
    public function getReadableReasonAttribute()
    {
        $reasonMap = [
            'products_shortage' => 'Products Shortage/Unavailability',
            'order_expired' => 'Order has been received 24+ hours ago',
            'customer_cannot_afford' => 'Customer can\'t afford the order',
            'customer_request' => 'Customer requested cancellation',
            'pharmacy_error' => 'Pharmacy processing error',
            'other' => 'Other'
        ];

        return $reasonMap[$this->cancellation_reason] ?? $this->cancellation_reason;
    }
}

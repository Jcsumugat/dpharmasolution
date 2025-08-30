<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Prescription;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'customer_id',
        'status',
        'order_date',
        'completed_at',
        'notes',
        'total_amount',
        'order_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Order status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the prescription that this order belongs to
     */
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Get the customer that this order belongs to
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the order items for this order
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items(): HasMany
{
    return $this->hasMany(OrderItem::class);
}

    /**
     * Get the sale associated with this order
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved orders
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for orders within date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    /**
     * Get all available order statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Check if order is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is approved
     */
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if order is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED,]);
    }

    /**
     * Calculate total amount from order items
     */
    public function calculateTotal()
    {
        return $this->orderItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute()
    {
        return $this->orderItems->sum('quantity');
    }

    /**
     * Get calculated total amount
     */
    public function getCalculatedTotalAttribute()
    {
        return $this->calculateTotal();
    }

    /**
     * Mark order as completed
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark order as approved
     */
    public function markAsApproved()
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
    }

    /**
     * Mark order as partially approved
     */


    /**
     * Mark order as cancelled
     */
    public function markAsCancelled()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically calculate total when order items change
        static::saved(function ($order) {
            if ($order->orderItems->isNotEmpty()) {
                $calculatedTotal = $order->calculateTotal();
                if ($order->total_amount != $calculatedTotal) {
                    $order->update(['total_amount' => $calculatedTotal]);
                }
            }
        });
    }
}

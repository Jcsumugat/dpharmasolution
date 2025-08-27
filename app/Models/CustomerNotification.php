<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomerNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'prescription_id', 
        'title',
        'message',
        'type',
        'is_read',
        'data'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    // Scopes
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    public function getIsRecentAttribute()
    {
        return $this->created_at->diffInHours() <= 24;
    }

    // Helper methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function markAsUnread()
    {
        $this->update(['is_read' => false]);
    }

    public static function createForCustomer($customerId, $prescriptionId, $title, $message, $type = 'general', $data = null)
    {
        return self::create([
            'customer_id' => $customerId,
            'prescription_id' => $prescriptionId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'is_read' => false
        ]);
    }

    public static function getUnreadCountForCustomer($customerId)
    {
        return self::forCustomer($customerId)->unread()->count();
    }

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        // Automatically set created_at and updated_at if not already set
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = Carbon::now();
            }
        });
    }
}
<?php

// app/Models/CustomerChat.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerChat extends Model
{
    protected $table = 'customers_chat';

    protected $fillable = [
        'customer_id',
        'email_address',
        'full_name',
        'is_online',
        'last_active',
        'chat_status'
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_active' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_id', 'customer_id');
    }

    public function activeConversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'customer_id', 'customer_id')
                    ->whereIn('status', ['active', 'pending'])
                    ->latest();
    }

    public function onlineStatus(): HasOne
    {
        return $this->hasOne(UserOnlineStatus::class, 'customer_id', 'customer_id');
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('chat_status', $status);
    }

    public function updateOnlineStatus($isOnline = true)
    {
        $this->update([
            'is_online' => $isOnline,
            'last_active' => now(),
            'chat_status' => $isOnline ? 'available' : 'offline'
        ]);

        // Update or create online status record
        UserOnlineStatus::updateOrCreate(
            ['customer_id' => $this->customer_id],
            [
                'is_online' => $isOnline,
                'last_seen' => now(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip()
            ]
        );
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'customer_id',
        'admin_id',
        'message',
        'message_type',
        'is_from_customer',
        'is_internal_note',
        'read_at'
    ];

    protected $casts = [
        'is_from_customer' => 'boolean',
        'is_internal_note' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'time_ago',
        'sender_name',
        'has_attachments'
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerChat::class, 'customer_id', 'customer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getSenderNameAttribute(): string
    {
        if ($this->is_from_customer) {
            return $this->customer->full_name ?? 'Customer';
        }

        return $this->admin->name ?? 'Admin';
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return $this->attachments()->exists();
    }

    public function scopeFromCustomer($query)
    {
        return $query->where('is_from_customer', true);
    }

    public function scopeFromAdmin($query)
    {
        return $query->where('is_from_customer', false);
    }

    public function scopeNotInternal($query)
    {
        return $query->where('is_internal_note', false);
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }
}

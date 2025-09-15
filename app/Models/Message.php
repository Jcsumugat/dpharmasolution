<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'message_type',
        'attachments',
        'is_read',
        'read_at',
        'is_internal_note',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'is_internal_note' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender (User or Customer).
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get sender name for display
     */
    public function getSenderNameAttribute(): string
    {
        if (!$this->sender) {
            return 'Unknown User';
        }

        if ($this->sender_type === 'App\Models\Customer') {
            return $this->sender->full_name ?? 'Customer';
        }

        if ($this->sender_type === 'App\Models\User') {
            return $this->sender->name ?? 'Admin';
        }

        return 'System';
    }

    /**
     * Get sender avatar/initials
     */
    public function getSenderAvatarAttribute(): string
    {
        if (!$this->sender) {
            return '👤';
        }

        if ($this->sender_type === 'App\Models\Customer') {
            $name = $this->sender->full_name ?? 'Customer';
            $parts = explode(' ', $name);
            return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        if ($this->sender_type === 'App\Models\User') {
            $name = $this->sender->name ?? 'Admin';
            $parts = explode(' ', $name);
            return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        return '🤖';
    }

    /**
     * Check if sender is admin
     */
    public function getIsAdminSenderAttribute(): bool
    {
        return $this->sender_type === 'App\Models\User';
    }

    /**
     * Check if sender is customer
     */
    public function getIsCustomerSenderAttribute(): bool
    {
        return $this->sender_type === 'App\Models\Customer';
    }

    /**
     * Get formatted message content
     */
    public function getFormattedMessageAttribute(): string
    {
        if ($this->message_type === 'system') {
            return "🔔 " . $this->message;
        }

        return $this->message;
    }

    /**
     * Get attachment URLs if any
     */
    public function getAttachmentUrlsAttribute(): array
    {
        if (!$this->attachments) {
            return [];
        }

        return array_map(function ($attachment) {
            return asset('storage/' . $attachment);
        }, $this->attachments);
    }

    /**
     * Check if message has attachments
     */
    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get time ago format
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for messages not from specific sender
     */
    public function scopeNotFromSender($query, $senderType, $senderId)
    {
        return $query->where(function ($q) use ($senderType, $senderId) {
            $q->where('sender_type', '!=', $senderType)
              ->orWhere('sender_id', '!=', $senderId);
        });
    }

    /**
     * Scope for public messages (not internal notes)
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    /**
     * Scope for internal notes only
     */
    public function scopeInternalNotes($query)
    {
        return $query->where('is_internal_note', true);
    }

    /**
     * Create a system message
     */
    public static function createSystemMessage($conversationId, $message)
    {
        return static::create([
            'conversation_id' => $conversationId,
            'sender_type' => 'system',
            'sender_id' => 0,
            'message' => $message,
            'message_type' => 'system',
            'is_read' => false,
        ]);
    }
}

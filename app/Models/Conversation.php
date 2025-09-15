<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'customer_id',
        'admin_id',
        'title',
        'type',
        'status',
        'priority',
        'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerChat::class, 'customer_id', 'customer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'id', 'conversation_id')->latest();
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function getUnreadCountForUser($userId, $isAdmin = true): int
    {
        $participant = $this->participants()
            ->where($isAdmin ? 'admin_id' : 'customer_id', $userId)
            ->first();

        if (!$participant || !$participant->last_read_message_id) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('id', '>', $participant->last_read_message_id)
            ->count();
    }

    public function markAsRead($userId, $isAdmin = true)
    {
        $lastMessage = $this->messages()->latest()->first();

        if ($lastMessage) {
            ConversationParticipant::updateOrCreate(
                [
                    'conversation_id' => $this->id,
                    $isAdmin ? 'admin_id' : 'customer_id' => $userId
                ],
                ['last_read_message_id' => $lastMessage->id]
            );
        }
    }
}

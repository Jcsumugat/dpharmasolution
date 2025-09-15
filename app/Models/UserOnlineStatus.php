<?php
// app/Models/UserOnlineStatus.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnlineStatus extends Model
{
    protected $table = 'user_online_status';

    protected $fillable = [
        'customer_id',
        'admin_id',
        'is_online',
        'last_seen',
        'user_agent',
        'ip_address'
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerChat::class, 'customer_id', 'customer_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeRecentlyActive($query, $minutes = 5)
    {
        return $query->where('last_seen', '>=', now()->subMinutes($minutes));
    }
}

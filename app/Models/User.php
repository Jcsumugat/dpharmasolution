<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_STAFF = 'staff';

    /**
     * The attributes that are mass assignable.
     * Only include fields that actually exist in your database
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        // Removed: 'phone', 'department', 'last_login_at' - these don't exist in your database
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_CUSTOMER,
    ];

    // Role-based check methods
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isStaff(): bool
    {
        return $role === self::ROLE_STAFF;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Get all available roles
     *
     * @return array<string>
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_CUSTOMER,
            self::ROLE_STAFF,
        ];
    }
    
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
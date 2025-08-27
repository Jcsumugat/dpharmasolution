<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $table = 'customers';
    

    public $incrementing = true;
    
    // If customer_id is not auto-incrementing (string-based)
    // public $incrementing = false;
    // protected $keyType = 'string';

    protected $fillable = [
        'customer_id',
        'full_name',
        'address',
        'birthdate',
        'sex',
        'email_address',
        'contact_number',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Auto-generate customer_id when creating new customers
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_id)) {
                // Find the highest customer number from properly formatted customer_ids
                $lastCustomerNumber = static::whereRaw("customer_id REGEXP '^CUST[0-9]+
}")
                    ->selectRaw('MAX(CAST(SUBSTRING(customer_id, 5) AS UNSIGNED)) as max_number')
                    ->value('max_number');
                
                $nextNumber = ($lastCustomerNumber ?? 0) + 1;
                $customer->customer_id = 'CUST' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}
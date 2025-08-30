<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class Prescription extends Model
{
    use HasFactory;

   protected $fillable = [
    'mobile_number',
    'notes',
    'file_path',
    'original_filename',
    'file_mime_type',
    'file_size',
    'is_encrypted',
    'encrypted_at',
    'token',
    'status',
    'user_id',
    'customer_id',
    'qr_code_path',
    'admin_message',
    'order_type',
];
protected $casts = [
        'customer_id' => 'integer',
        'user_id' => 'integer',
    ];


    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }


public function order()
{
    return $this->hasOne(Order::class, 'prescription_id');
}
}

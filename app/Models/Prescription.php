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
        // New duplicate detection fields
        'file_hash',
        'perceptual_hash',
        'extracted_text',
        'prescription_number',
        'doctor_name',
        'prescription_issue_date',
        'prescription_expiry_date',
        'duplicate_check_status',
        'duplicate_of_id',
        'similarity_score',
        'duplicate_checked_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'user_id' => 'integer',
        'prescription_issue_date' => 'date',
        'prescription_expiry_date' => 'date',
        'duplicate_checked_at' => 'datetime',
        'similarity_score' => 'decimal:2',
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

    /**
     * Get the prescription this one is a duplicate of
     */
    public function duplicateOf()
    {
        return $this->belongsTo(Prescription::class, 'duplicate_of_id');
    }

    /**
     * Get prescriptions that are duplicates of this one
     */
    public function duplicates()
    {
        return $this->hasMany(Prescription::class, 'duplicate_of_id');
    }
}

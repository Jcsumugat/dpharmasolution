<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'sender_type',
        'sender_id',
        'message',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function sender()
    {
        if ($this->sender_type === 'admin') {
            return $this->belongsTo(User::class, 'sender_id'); // Assuming admin users
        } else {
            return $this->belongsTo(Customer::class, 'sender_id');
        }
    }
}

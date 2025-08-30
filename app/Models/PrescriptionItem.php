<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'product_id',
        'quantity',
        'batch_id'
    ];

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

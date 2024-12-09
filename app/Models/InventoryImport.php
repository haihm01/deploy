<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryImport extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['import_price', 'quantity', 'variant_id', 'supplier_id','batch_number'];
    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
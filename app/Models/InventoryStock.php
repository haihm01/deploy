<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;
    protected $fillable = ['quantity', 'variant_id'];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}


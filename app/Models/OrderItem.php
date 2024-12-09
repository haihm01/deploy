<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'quantity',
        'order_item_attribute',
        'unit_price',
        'total_price',
        'product_name',
        'product_image',
        'variant_id',
        'unit_cost',
        'profit'
    ];

    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);

    }
   
    
    
}
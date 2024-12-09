<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'discount_amount',
        'coupon_id'
    ];
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'used_at',
    ];

    public function coupon() {
        return $this->belongsTo(Coupon::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
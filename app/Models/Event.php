<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'is_active',
    ];

    // Thiết lập quan hệ với model Coupon
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'event_coupons', 'event_id', 'coupon_id');

    }


}

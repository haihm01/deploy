<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'coupon_id',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Mối quan hệ với model Coupon
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}

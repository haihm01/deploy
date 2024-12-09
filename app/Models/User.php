<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tel',
        'address_id',
        'avatar',
        'provider',
        'provider_id',
        'provider_token',
        'remember_token',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'user_id', 'id');
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_user');
    }

    // một người dùng có thể tạo nhiều đánh giá
    public function rates()
    {
        return $this->hasMany(Rate::class);
    }

    public function addresses()
    {
        return $this->belongsToMany(Address::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

}
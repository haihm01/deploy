<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_ORDER = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'preparing_goods' => 'Đang chuẩn bị hàng',
        'shipping' => 'Đang vận chuyển',
        'delivered' => 'Đã giao hàng',
        'canceled' => 'Đơn hàng đã bị hủy',
    ];
    protected $fillable = [
        'user_id',
        'grand_total',
        'final_total',
        'payment_method',
        'status_order',
        'notes',
        'name',
        'tel',
        'address',
        'paid_at',
        'completed_at',
        'code'
    ];

    const STATUS_ORDER_PENDING = 'pending';
    const STATUS_ORDER_CONFIRMED = 'confirmed';
    const STATUS_ORDER_PREPARING_GOODS = 'preparing_goods';
    const STATUS_ORDER_SHIPPING = 'shipping';
    const STATUS_ORDER_DELIVERED = 'delivered';
    const STATUS_ORDER_CANCELED = 'canceled';


    public function items() 
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }


    public function coupons()
    {
        return $this->hasMany(OrderCoupon::class);
    }


     // Thêm mối quan hệ với Rate

     public function rates()
     {
         return $this->hasMany(Rate::class);
     }

}
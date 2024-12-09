<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'rating', 'product_id', 'user_id','order_id'];

     // Liên kết với người dùng
     public function user()
     {
         return $this->belongsTo(User::class);
     }

       // Liên kết với sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

     // Liên kết với đơn hàng
     public function order()
     {
         return $this->belongsTo(Order::class);
     }
}

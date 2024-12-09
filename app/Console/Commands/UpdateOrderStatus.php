<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class UpdateOrderStatus extends Command
{
    protected $signature = 'order:update-status';
    protected $description = 'Tự động cập nhật trạng thái đơn hàng sang "Đã giao hàng" sau 7 ngày nếu chưa cập nhật.';

    public function handle()
    {
         $orders = Order::where('status_order', Order::STATUS_ORDER_SHIPPING)
         ->where('updated_at', '<=', now()->subDay(7))
         ->get();

     foreach ($orders as $order) {
         $order->update([
             'status_order' => Order::STATUS_ORDER_DELIVERED,
             'completed_at' => now(),
         ]);

         $this->info('Đã cập nhật trạng thái đơn hàng: ' . $order->code);
     }

     $this->info('Hoàn thành việc cập nhật trạng thái.');
 }

}


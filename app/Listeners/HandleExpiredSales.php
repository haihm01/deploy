<?php
namespace App\Listeners;

use App\Events\CheckExpiredSalePrices;
use App\Models\Variant;
use App\Events\SaleExpiredRealtime; // Nếu dùng Pusher
use Carbon\Carbon;

class HandleExpiredSales
{
    public function __construct()
    {
        // Constructor nếu cần
    }

    public function handle(CheckExpiredSalePrices $event)
    {
        // Tìm các sale hết hạn
        $expiredSales = Variant::whereNotNull('end_sale')
            ->where('end_sale', '<=', Carbon::now())
            ->get();

        foreach ($expiredSales as $sale) {
            // Đặt cả sale_price và end_sale về null
            $sale->sale_price = null;
            $sale->end_sale = null;
            $sale->save();

            // Phát sự kiện nếu dùng Pusher
            broadcast(new SaleExpiredRealtime($sale));
        }
    }

}

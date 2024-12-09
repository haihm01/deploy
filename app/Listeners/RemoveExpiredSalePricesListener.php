<?php

namespace App\Listeners;
use App\Events\CheckExpiredSalePrices;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RemoveExpiredSalePricesListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CheckExpiredSalePrices $event)
    {
        // Lấy các variant có end_sale đã qua
        $variants = Variant::where('end_sale', '<=', Carbon::now())->get();

        foreach ($variants as $variant) {
            $variant->sale_price = null;
            $variant->end_sale = null;
            $variant->save();
        }
    }
}

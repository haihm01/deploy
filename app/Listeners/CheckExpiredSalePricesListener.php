<?php

namespace App\Listeners;

use App\Events\CheckExpiredSalePrices;
use App\Models\Variant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckExpiredSalePricesListener
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
    public function handle()
{
    $now = now();
    Log::info('Handling event at:', ['time' => $now]);

    // Kiểm tra variants hết hạn sale
    $variants = Variant::whereNotNull('end_sale')
        ->where('end_sale', '<=', $now)
        ->get();

    Log::info('Expired variants found:', ['count' => $variants->count()]);

    foreach ($variants as $variant) {
        $variant->update(['sale_price' => null, 'end_sale' => null]);
        Log::info('Updated variant:', ['id' => $variant->id]);
    }
}

}

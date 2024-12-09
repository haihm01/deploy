<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class DeleteCart implements ShouldQueue
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
    public function handle(OrderShipped $event): void
    {
        $cartData = $event->order->idCart;
        $user = $event->order->user;

        Cart::query()->where('user_id', $user->id)
        ->whereIn('id',  $cartData )
        ->delete();
    }
}
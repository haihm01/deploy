<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class SaleExpiredRealtime implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $variant;

    public function __construct($variant)
    {
        $this->variant = $variant;

    }
    public function broadcastWith()
    {
        Log::info('Broadcasting SaleExpiredRealtime:', [
            'variant_id' => $this->variant->id,
            'time' => now()->toDateTimeString()
        ]);

        return [
            'variant_id' => $this->variant->id,
            'time' => now()->toDateTimeString(),
        ];
    }

    public function broadcastOn()
    {
        return new Channel('sales-expired');
    }

    public function broadcastAs()
    {
        return 'sale.expired.realtime';
    }
}

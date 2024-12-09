<?php
namespace App\Events;

use App\Models\Variant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckExpiredSalePrices implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $variant;

    // Constructor để truyền dữ liệu vào sự kiện
    public function __construct(Variant $variant)
    {
        $this->variant = $variant;
    }

    // Định nghĩa channel sẽ broadcast sự kiện này
    public function broadcastOn()
    {
        return new Channel('sale-expired');
    }

    // Định nghĩa tên sự kiện
    public function broadcastAs()
    {
        return 'sale.expired.realtime';
    }

    // Dữ liệu sẽ được gửi cùng sự kiện
    public function broadcastWith()
    {
        return [
            'variant_id' => $this->variant->id,
            'sale_price' => $this->variant->sale_price,
        ];
    }
}

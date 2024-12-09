<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Models\InventoryImport;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAttribute;
use App\Models\OrderItemAttributeValue;
use App\Models\Variant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InsertOrderItems
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

        $orderId = $event->order->id;
        $objectData =$event->order->items;
        \Log::info('orser', (array) $event->order );
        
        $orderItems = [];
        
        foreach ($objectData as $value) {
            // Log::info($value->variant->product->images[0]);

            if($value->variant->image){
                $productImage = $value->variant->image;
            }else {
                $urls = json_decode($value->variant->product->images);
                $productImage = $urls[0]; // Lấy URL đầu tiên
            }

            $orderItem['order_id'] = $orderId;
            $orderItem['variant_id'] = $value->variant->id;
            $orderItem['order_item_attribute'] = json_encode($value->variant->attribute_values);
            $orderItem['product_name'] = $value->variant->product->name;
            $orderItem['product_image'] = json_encode($productImage) ;
            $orderItem['quantity'] = $value->quantity;
            $orderItem['unit_price'] = $value->variant->sale_price ?: $value->variant->price;
            $orderItem['total_price'] = $value->quantity * ($value->variant->sale_price ?: $value->variant->price);

            
            InventoryStock::query()
            ->where('variant_id', $value->variant_id)
            ->decrement('quantity', $value->quantity); 
            

            $remainingQuantity = $value->quantity;

            $stocks = InventoryImport::query()
            ->where('variant_id', $value->variant_id)
            ->where('quantity', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

            $totalCost = 0;

            foreach ($stocks as $stock) {
                // Kiểm tra nếu còn đủ hàng
                if ($remainingQuantity <= 0) {
                    break; // Nếu đã mua đủ, thoát khỏi vòng lặp
                }
        
                // Tính số lượng sẽ trừ
                $quantityAvailable = $stock->quantity;
                $unitPrice = $stock->import_price;

                if ($quantityAvailable > 0) {
                    // Nếu còn hàng trong kho
                    if ($quantityAvailable >= $remainingQuantity) {


                        $stock->quantity -= $remainingQuantity;
                        $totalCost += $remainingQuantity * $unitPrice;
                        $remainingQuantity = 0; // Đã mua xong

                    } else {
                        $totalCost += $quantityAvailable * $unitPrice;
                        $remainingQuantity -= $quantityAvailable;
                        $stock->quantity = 0;
                    }
                        


                    $stock->save();
                    if($stock->quantity === 0){
                        $stock->delete();
                    }
                }
                
                
            }
            \Log::info('tutolCosst', (array) $totalCost);
            $unitCost = $totalCost / $value->quantity;
            $orderItem['unit_cost'] = $unitCost;
            $orderItem['profit'] = ($orderItem['unit_price'] - $orderItem['unit_cost']) * $value->quantity;
            $orderItems[] = $orderItem;

        }           
        OrderItem::insert($orderItems);

        $orderProfit = OrderItem::query()
        ->where('order_id', $orderId)
        ->sum('profit');

        $profit = $orderProfit - ($event->order->grand_total - $event->order->final_total);

        Order::query()->where('id', $orderId)->update([
            'profit' => $profit
        ]);
    }
}
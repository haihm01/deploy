<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Kiểm tra nếu coupons đã được tải và là một collection
        $coupons = $this->whenLoaded('coupons');

        // Khởi tạo biến tổng xác suất trúng thưởng
        $totalWinningProbability = $coupons ? $coupons->sum('winning_probability') : 0;

        // Tính toán các coupon và tỷ lệ phần trăm trúng thưởng
        $couponsWithProbability = $coupons ? $coupons->map(function ($coupon) use ($totalWinningProbability) {
            $probabilityPercentage = ($totalWinningProbability > 0)
                ? ($coupon->winning_probability / $totalWinningProbability) * 100
                : 0;

            return [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'code' => $coupon->code,
                'discount' => $coupon->discount,
                'discount_type' => $coupon->discount_type,
                'usage_limit' => $coupon->usage_limit,
                'min_order_value' => $coupon->min_order_value,
                'max_order_value' => $coupon->max_order_value,
                'status' => $coupon->status,
                'winning_probability' => $coupon->winning_probability,
                'probability_percentage' => round($probabilityPercentage, 2),
                'created_at' => $coupon->created_at,
                'updated_at' => $coupon->updated_at,
                'pivot' => [
                    'event_id' => $coupon->pivot->event_id,
                    'coupon_id' => $coupon->pivot->coupon_id,
                ],
            ];
        }) : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'coupons' => $couponsWithProbability,
        ];
    }
}

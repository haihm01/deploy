<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalWinningProbability = $this->event ? $this->event->coupons->sum('winning_probability') : 0;

        // Tính tỷ lệ phần trăm trúng thưởng cho coupon
        $probabilityPercentage = ($totalWinningProbability > 0) ? ($this->winning_probability / $totalWinningProbability) * 100 : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'usage_limit' => $this->usage_limit,
            'min_order_value' => $this->min_order_value,
            'max_order_value' => $this->max_order_value,
            'status' => $this->status,
            'winning_probability' => $this->winning_probability,
            'type' => 'event', // Hoặc lấy từ bảng nếu có
            'probability_percentage' => round($probabilityPercentage, 2), // Tỷ lệ phần trăm trúng thưởng
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'pivot' => [
                'event_id' => $this->pivot->event_id,
                'coupon_id' => $this->pivot->coupon_id,
            ],
        ];


    }
}

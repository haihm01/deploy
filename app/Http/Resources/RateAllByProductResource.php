<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateAllByProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    { 
         return [
        'avatar' => $this->user->avatar,
        'user_name' => $this->user->name,
        'rating' => $this->rating,
        'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        'content' => $this->content,
        'attribute_values' => $this->product->variants->flatMap(function ($variant) {
            return $variant->attributeValues;
        }),    
        
        ];
    }
}

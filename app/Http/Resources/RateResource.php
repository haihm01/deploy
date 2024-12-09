<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rating' => $this->rating,
            'content' => $this->content,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user' => [
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

            // 'is_active' => $this->is_active,



        return [
            'id' => $this->id,
            'text' => $this->text,
            'answer_id' => $this->answer_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
        ];

     }
}

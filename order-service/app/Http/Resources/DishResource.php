<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DishResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'recipe' => $this->recipe,
            'image_url' => $this->image,
            'article' => $this->article,
            'price' => $this->price,
            'amount' => $this->amount
        ];
    }
}

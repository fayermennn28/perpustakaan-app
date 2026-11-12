<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'author'      => $this->author,
            'publisher'   => $this->publisher,
            'year'        => $this->year,
            'isbn'        => $this->isbn,
            'stock'       => $this->stock,
            'available'   => $this->stock > 0,
            'description' => $this->description,
            'cover_url'   => $this->cover ? asset('storage/' . $this->cover) : null,
            'category'    => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
        ];
    }
}
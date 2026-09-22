<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => $this->genres->map(fn ($genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
            ]),
            'average_rating' => $this->reviews_avg_rating === null
                ? null
                : round((float) $this->reviews_avg_rating, 1),
            'review_count' => $this->reviews_count,
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'is_draft' => $this->is_draft,
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}

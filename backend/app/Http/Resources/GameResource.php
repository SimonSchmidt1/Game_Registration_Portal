<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'rating' => $this->rating,
            'views' => $this->views,
            'release_date' => $this->release_date,
            'team' => $this->whenLoaded('team', fn() => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'academic_year' => $this->whenLoaded('academicYear', fn() => [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ]),
            'trailer_path' => $this->trailer_path,
            'trailer_url' => $this->trailer_url,
            'splash_screen_path' => $this->splash_screen_path,
            'export_path' => $this->export_path,
            'source_code_path' => $this->source_code_path,
        ];
    }
}

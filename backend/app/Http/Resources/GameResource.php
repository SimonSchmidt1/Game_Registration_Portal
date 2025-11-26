<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type ?? 'game',
            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                ];
            }),
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            'rating' => $this->rating ?? null,
            'views' => $this->views ?? null,
            'splash_screen_path' => $this->splash_screen_path,
            'trailer_path' => $this->trailer_path,
            'source_code_path' => $this->source_code_path,
            'export_path' => $this->export_path,
            'release_date' => $this->release_date,
            'created_at' => $this->created_at,
        ];
    }
}

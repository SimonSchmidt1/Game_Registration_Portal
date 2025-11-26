<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'invite_code' => $this->invite_code,
            'academic_year' => $this->whenLoaded('academicYear', function () {
                return [
                    'id' => $this->academicYear->id,
                    'name' => $this->academicYear->name,
                ];
            }),
            'members' => $this->whenLoaded('users', function () {
                return $this->users->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->pivot->role ?? null,
                ]);
            }),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray($request): array
    {
        $authId = optional($request->user())->id;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'invite_code' => $this->invite_code,
            'academic_year' => $this->academicYear?->only(['id','name']),
            'is_scrum_master' => (bool)($this->scrum_master_id === $authId),
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'role' => $m->pivot?->role_in_team ?? ($this->scrum_master_id === $m->id ? 'scrum_master' : 'member'),
                    ];
                });
            }),
        ];
    }
}

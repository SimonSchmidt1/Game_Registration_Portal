<?php

namespace App\DTO;

use App\Enums\ProjectType;

class ProjectData
{
    public function __construct(
        public readonly ProjectType $type,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $schoolType,
        public readonly ?int $yearOfStudy,
        public readonly string $subject,
        public readonly ?string $releaseDate,
        public readonly int $teamId,
        public readonly array $attachments = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ProjectType::from($data['type'] ?? 'game'),
            $data['title'],
            $data['description'] ?? null,
            $data['school_type'] ?? 'zs',
            $data['year_of_study'] ?? null,
            $data['subject'] ?? '',
            $data['release_date'] ?? null,
            (int)$data['team_id'],
            $data['attachments'] ?? []
        );
    }
}

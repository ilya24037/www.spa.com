<?php

namespace App\Domain\Ad\DTOs;

class AdContentData
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $specialty,
        public readonly ?string $education,
        public readonly ?array $languages,
        public readonly ?array $services,
        public readonly ?array $skills,
        public readonly ?array $tags
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            description: $data['description'],
            specialty: $data['specialty'] ?? null,
            education: $data['education'] ?? null,
            languages: $data['languages'] ?? null,
            services: $data['services'] ?? null,
            skills: $data['skills'] ?? null,
            tags: $data['tags'] ?? null
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'specialty' => $this->specialty,
            'education' => $this->education,
            'languages' => $this->languages,
            'services' => $this->services,
            'skills' => $this->skills,
            'tags' => $this->tags,
        ], fn($value) => $value !== null);
    }
}
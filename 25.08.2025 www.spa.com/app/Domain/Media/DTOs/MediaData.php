<?php

namespace App\Domain\Media\DTOs;

use App\Enums\MediaType;
use App\Enums\MediaStatus;

class MediaData
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly MediaType $type,
        public readonly string $filename,
        public readonly string $path,
        public readonly string $mimeType,
        public readonly int $size,
        public readonly MediaStatus $status,
        public readonly ?string $url,
        public readonly ?string $thumbnailUrl,
        public readonly ?string $originalName,
        public readonly ?string $altText,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?int $sortOrder,
        public readonly ?array $metadata = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            entityType: $data['entity_type'],
            entityId: $data['entity_id'],
            type: MediaType::from($data['type']),
            filename: $data['filename'],
            path: $data['path'],
            mimeType: $data['mime_type'],
            size: (int) $data['size'],
            status: isset($data['status']) ? MediaStatus::from($data['status']) : MediaStatus::PENDING,
            url: $data['url'] ?? null,
            thumbnailUrl: $data['thumbnail_url'] ?? null,
            originalName: $data['original_name'] ?? null,
            altText: $data['alt_text'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'type' => $this->type->value,
            'filename' => $this->filename,
            'path' => $this->path,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'status' => $this->status->value,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnailUrl,
            'original_name' => $this->originalName,
            'alt_text' => $this->altText,
            'title' => $this->title,
            'description' => $this->description,
            'sort_order' => $this->sortOrder,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function getFormattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    public function isDocument(): bool
    {
        return in_array($this->mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
}
<?php

namespace App\Infrastructure\Media\Services;

use App\Enums\MediaType;
use Illuminate\Http\UploadedFile;

/**
 * Сервис валидации медиафайлов
 */
class MediaValidationService
{
    /**
     * Валидация файла
     */
    public function validate(UploadedFile $file, MediaType $type): void
    {
        $this->validateFileSize($file, $type);
        $this->validateMimeType($file, $type);
        $this->validateExtension($file, $type);
    }

    /**
     * Валидация размера файла
     */
    private function validateFileSize(UploadedFile $file, MediaType $type): void
    {
        if ($file->getSize() > $type->getMaxFileSize()) {
            throw new \InvalidArgumentException('Файл слишком большой');
        }
    }

    /**
     * Валидация MIME-типа
     */
    private function validateMimeType(UploadedFile $file, MediaType $type): void
    {
        if (!in_array($file->getMimeType(), $type->getMimeTypes())) {
            throw new \InvalidArgumentException('Неподдерживаемый MIME-тип');
        }
    }

    /**
     * Валидация расширения файла
     */
    private function validateExtension(UploadedFile $file, MediaType $type): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $type->getAllowedExtensions())) {
            throw new \InvalidArgumentException('Неподдерживаемое расширение файла');
        }
    }
}
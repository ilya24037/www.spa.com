<?php

namespace App\Domain\User\ValueObjects;

/**
 * Value Object для контактной информации пользователя
 */
final class ContactInfo
{
    private array $validatedSocialLinks = [];

    public function __construct(
        public readonly ?string $website = null,
        ?array $social_links = null,
    ) {
        if ($social_links !== null) {
            $this->validatedSocialLinks = $this->validateSocialLinks($social_links);
        }
    }

    /**
     * Создать из массива
     */
    public static function fromArray(array $data): self
    {
        return new self(
            website: isset($data['website']) ? self::sanitizeUrl($data['website']) : null,
            social_links: $data['social_links'] ?? null,
        );
    }

    /**
     * Конвертировать в массив
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->website !== null) $data['website'] = $this->website;
        if (!empty($this->validatedSocialLinks)) $data['social_links'] = $this->validatedSocialLinks;

        return $data;
    }

    /**
     * Получить социальные ссылки
     */
    public function getSocialLinks(): array
    {
        return $this->validatedSocialLinks;
    }

    /**
     * Валидировать данные
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->website !== null) {
            if (!filter_var($this->website, FILTER_VALIDATE_URL)) {
                $errors['website'] = 'Некорректный URL веб-сайта';
            } elseif (!preg_match('/^https?:\/\//i', $this->website)) {
                $errors['website'] = 'URL должен начинаться с http:// или https://';
            } elseif (strlen($this->website) > 255) {
                $errors['website'] = 'URL слишком длинный';
            }
        }

        if (!empty($this->validatedSocialLinks)) {
            foreach ($this->validatedSocialLinks as $platform => $url) {
                if (!$this->isValidSocialUrl($platform, $url)) {
                    $errors['social_links'] = "Некорректная ссылка для $platform";
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Проверить валидность
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Валидация социальных ссылок
     */
    private function validateSocialLinks(array $links): array
    {
        $validated = [];
        $allowedPlatforms = ['vk', 'telegram', 'instagram', 'whatsapp', 'youtube', 'tiktok'];
        
        foreach ($links as $platform => $url) {
            if (!in_array($platform, $allowedPlatforms, true)) {
                continue;
            }
            
            $sanitizedUrl = self::sanitizeUrl($url);
            if ($sanitizedUrl && $this->isValidSocialUrl($platform, $sanitizedUrl)) {
                $validated[$platform] = $sanitizedUrl;
            }
        }
        
        return $validated;
    }

    /**
     * Проверка URL социальной сети
     */
    private function isValidSocialUrl(string $platform, string $url): bool
    {
        $patterns = [
            'vk' => '/^https:\/\/(www\.)?vk\.com\/.+$/i',
            'telegram' => '/^https:\/\/t\.me\/.+$/i',
            'instagram' => '/^https:\/\/(www\.)?instagram\.com\/.+$/i',
            'whatsapp' => '/^https:\/\/(wa\.me|api\.whatsapp\.com)\/.+$/i',
            'youtube' => '/^https:\/\/(www\.)?(youtube\.com|youtu\.be)\/.+$/i',
            'tiktok' => '/^https:\/\/(www\.)?tiktok\.com\/.+$/i',
        ];
        
        return isset($patterns[$platform]) && preg_match($patterns[$platform], $url);
    }

    /**
     * Санитизация URL
     */
    private static function sanitizeUrl(?string $value): ?string
    {
        if ($value === null) return null;
        
        $value = trim($value);
        
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        if (!preg_match('/^https?:\/\//i', $value)) {
            return null;
        }
        
        return $value;
    }
}
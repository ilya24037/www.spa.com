<?php

namespace App\Domain\User\DTOs;

class UserSettingsData
{
    public function __construct(
        public readonly bool $emailNotifications,
        public readonly bool $smsNotifications,
        public readonly bool $pushNotifications,
        public readonly bool $marketingEmails,
        public readonly bool $showPhone,
        public readonly bool $showEmail,
        public readonly bool $publicProfile,
        public readonly ?string $theme,
        public readonly ?string $locale,
        public readonly ?array $notificationSettings,
        public readonly ?array $privacySettings
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            emailNotifications: $data['email_notifications'] ?? true,
            smsNotifications: $data['sms_notifications'] ?? false,
            pushNotifications: $data['push_notifications'] ?? true,
            marketingEmails: $data['marketing_emails'] ?? false,
            showPhone: $data['show_phone'] ?? false,
            showEmail: $data['show_email'] ?? false,
            publicProfile: $data['public_profile'] ?? true,
            theme: $data['theme'] ?? 'light',
            locale: $data['locale'] ?? 'ru',
            notificationSettings: $data['notification_settings'] ?? null,
            privacySettings: $data['privacy_settings'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'email_notifications' => $this->emailNotifications,
            'sms_notifications' => $this->smsNotifications,
            'push_notifications' => $this->pushNotifications,
            'marketing_emails' => $this->marketingEmails,
            'show_phone' => $this->showPhone,
            'show_email' => $this->showEmail,
            'public_profile' => $this->publicProfile,
            'theme' => $this->theme,
            'locale' => $this->locale,
            'notification_settings' => $this->notificationSettings,
            'privacy_settings' => $this->privacySettings,
        ];
    }
}
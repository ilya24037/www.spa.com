<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Settings extends Page
{
    // protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    public function getView(): string
    {
        return 'filament.pages.settings';
    }

    // protected static ?string $navigationGroup = 'Настройки';

    public static function getNavigationGroup(): ?string
    {
        return 'Настройки';
    }

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Настройки системы';
    }

    public function getHeading(): string
    {
        return 'Настройки системы';
    }

    public function getSubheading(): ?string
    {
        return 'Управление основными настройками сайта и системы';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Site settings
            'site_name' => config('app.name', 'MASSAGIST'),
            'site_description' => config('app.description', 'Маркетплейс услуг массажа'),
            'site_keywords' => config('app.keywords', ''),
            'contact_email' => config('mail.from.address'),
            'contact_phone' => config('app.phone', ''),
            'contact_address' => config('app.address', ''),

            // Email settings
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_username' => config('mail.mailers.smtp.username'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),

            // Payment settings
            'payment_enabled' => config('payments.enabled', true),
            'payment_commission' => config('payments.commission', 10),
            'min_withdrawal' => config('payments.min_withdrawal', 1000),

            // Notification settings
            'notifications_enabled' => config('notifications.enabled', true),
            'email_notifications' => config('notifications.email', true),
            'sms_notifications' => config('notifications.sms', false),
            'push_notifications' => config('notifications.push', true),

            // SEO settings
            'seo_title' => config('seo.title', ''),
            'seo_description' => config('seo.description', ''),
            'google_analytics' => config('services.google.analytics_id', ''),
            'yandex_metrika' => config('services.yandex.metrika_id', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // Site Settings Tab
                        Forms\Components\Tabs\Tab::make('Сайт')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Основные настройки')
                                    ->description('Основная информация о сайте')
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Название сайта')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('site_description')
                                            ->label('Описание сайта')
                                            ->maxLength(500)
                                            ->rows(3),

                                        Forms\Components\Textarea::make('site_keywords')
                                            ->label('Ключевые слова')
                                            ->maxLength(500)
                                            ->rows(2)
                                            ->hint('Ключевые слова через запятую'),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make('Контактная информация')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('Email для связи')
                                            ->email()
                                            ->required(),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Телефон для связи')
                                            ->tel(),

                                        Forms\Components\Textarea::make('contact_address')
                                            ->label('Адрес')
                                            ->rows(2),
                                    ])
                                    ->columns(2),
                            ]),

                        // Email Settings Tab
                        Forms\Components\Tabs\Tab::make('Email')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Настройки почты')
                                    ->description('Конфигурация SMTP для отправки писем')
                                    ->schema([
                                        Forms\Components\Select::make('mail_driver')
                                            ->label('Драйвер почты')
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'sendmail' => 'Sendmail',
                                                'mailgun' => 'Mailgun',
                                                'ses' => 'Amazon SES',
                                            ])
                                            ->default('smtp'),

                                        Forms\Components\TextInput::make('mail_host')
                                            ->label('SMTP хост')
                                            ->required(),

                                        Forms\Components\TextInput::make('mail_port')
                                            ->label('SMTP порт')
                                            ->numeric()
                                            ->default(587),

                                        Forms\Components\TextInput::make('mail_username')
                                            ->label('SMTP пользователь')
                                            ->required(),

                                        Forms\Components\Select::make('mail_encryption')
                                            ->label('Шифрование')
                                            ->options([
                                                'tls' => 'TLS',
                                                'ssl' => 'SSL',
                                                null => 'Без шифрования',
                                            ])
                                            ->default('tls'),
                                    ])
                                    ->columns(2),
                            ]),

                        // Payment Settings Tab
                        Forms\Components\Tabs\Tab::make('Платежи')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make('Настройки платежей')
                                    ->schema([
                                        Forms\Components\Toggle::make('payment_enabled')
                                            ->label('Включить платежи')
                                            ->default(true),

                                        Forms\Components\TextInput::make('payment_commission')
                                            ->label('Комиссия (%)')
                                            ->numeric()
                                            ->min(0)
                                            ->max(100)
                                            ->default(10)
                                            ->suffix('%'),

                                        Forms\Components\TextInput::make('min_withdrawal')
                                            ->label('Минимальная сумма вывода (₽)')
                                            ->numeric()
                                            ->min(0)
                                            ->default(1000)
                                            ->suffix('₽'),
                                    ])
                                    ->columns(3),
                            ]),

                        // Notification Settings Tab
                        Forms\Components\Tabs\Tab::make('Уведомления')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Forms\Components\Section::make('Настройки уведомлений')
                                    ->schema([
                                        Forms\Components\Toggle::make('notifications_enabled')
                                            ->label('Включить уведомления')
                                            ->default(true),

                                        Forms\Components\Toggle::make('email_notifications')
                                            ->label('Email уведомления')
                                            ->default(true),

                                        Forms\Components\Toggle::make('sms_notifications')
                                            ->label('SMS уведомления')
                                            ->default(false),

                                        Forms\Components\Toggle::make('push_notifications')
                                            ->label('Push уведомления')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        // SEO Settings Tab
                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('SEO настройки')
                                    ->schema([
                                        Forms\Components\TextInput::make('seo_title')
                                            ->label('SEO заголовок')
                                            ->maxLength(60)
                                            ->hint('Рекомендуется до 60 символов'),

                                        Forms\Components\Textarea::make('seo_description')
                                            ->label('SEO описание')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->hint('Рекомендуется до 160 символов'),

                                        Forms\Components\TextInput::make('google_analytics')
                                            ->label('Google Analytics ID')
                                            ->placeholder('GA-XXXXXXXXX-X'),

                                        Forms\Components\TextInput::make('yandex_metrika')
                                            ->label('Yandex Metrika ID')
                                            ->numeric(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Сохранить настройки')
                ->action('save')
                ->color('primary'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Here you would typically save to database or config files
        // For this example, we'll cache the settings
        Cache::put('app_settings', $data, now()->addDays(30));

        Notification::make()
            ->title('Настройки сохранены')
            ->body('Настройки системы успешно обновлены.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
<?php

namespace App\Filament\Resources;

use App\Domain\Ad\Models\Complaint;
use App\Domain\Ad\Services\ComplaintModerationService;
use App\Domain\Admin\Traits\LogsAdminActions;
use App\Domain\Admin\Services\AdminActionsService;
use App\Filament\Resources\ComplaintResource\Pages;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|null $navigationLabel = 'Жалобы';

    protected static string|null $modelLabel = 'Жалоба';

    protected static string|null $pluralModelLabel = 'Жалобы';

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return Complaint::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Информация о жалобе')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('ad_id')
                            ->label('Объявление')
                            ->relationship('ad', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает рассмотрения',
                                'resolved' => 'Разрешена',
                                'rejected' => 'Отклонена',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Причина жалобы')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Решение по жалобе')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('resolved_by')
                            ->label('Администратор')
                            ->relationship('resolver', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Дата решения')
                            ->visible(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\Textarea::make('resolution_note')
                            ->label('Комментарий администратора')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (string $context): bool => $context === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ad.title')
                    ->label('Объявление')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Complaint $record): string {
                        return $record->ad->title ?? '';
                    })
                    ->url(fn (Complaint $record): string =>
                        AdResource::getUrl('view', ['record' => $record->ad_id])
                    ),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Заявитель')
                    ->searchable()
                    ->description(fn (Complaint $record): string =>
                        $record->user->email ?? ''
                    ),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Complaint $record): string {
                        return $record->reason;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'resolved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'resolved' => 'Разрешена',
                        'rejected' => 'Отклонена',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('resolver.name')
                    ->label('Обработал')
                    ->toggleable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Подана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Решена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает рассмотрения',
                        'resolved' => 'Разрешена',
                        'rejected' => 'Отклонена',
                    ])
                    ->default('pending'),

                Tables\Filters\Filter::make('unresolved')
                    ->label('Нерассмотренные')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->default(),

                Tables\Filters\Filter::make('today')
                    ->label('За сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('За неделю')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('created_at', '>=', now()->startOfWeek())
                    ),
            ])
            ->actions([
                ViewAction::make(),

                // Разрешить жалобу
                Action::make('resolve')
                    ->label('Разрешить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Complaint $record): bool => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('resolution_note')
                            ->label('Комментарий к решению')
                            ->placeholder('Опишите принятое решение...')
                            ->required()
                            ->rows(3),

                        Forms\Components\Toggle::make('block_ad')
                            ->label('Заблокировать объявление')
                            ->default(true)
                            ->helperText('Объявление будет заблокировано после разрешения жалобы'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Разрешить жалобу')
                    ->modalDescription('Вы уверены, что хотите разрешить эту жалобу? Объявление может быть заблокировано.')
                    ->action(function (Complaint $record, array $data): void {
                        app(ComplaintModerationService::class)->resolve(
                            $record,
                            auth()->user(),
                            $data['resolution_note'],
                            $data['block_ad'] ?? false
                        );

                        Notification::make()
                            ->title('Жалоба разрешена')
                            ->body($data['block_ad'] ? 'Объявление заблокировано' : 'Объявление оставлено активным')
                            ->success()
                            ->send();
                    }),

                // Отклонить жалобу
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Complaint $record): bool => $record->isPending())
                    ->form([
                        Forms\Components\Textarea::make('resolution_note')
                            ->label('Причина отклонения')
                            ->placeholder('Укажите причину отклонения жалобы...')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Отклонить жалобу')
                    ->modalDescription('Вы уверены, что хотите отклонить эту жалобу?')
                    ->action(function (Complaint $record, array $data): void {
                        app(ComplaintModerationService::class)->reject(
                            $record,
                            auth()->user(),
                            $data['resolution_note']
                        );

                        Notification::make()
                            ->title('Жалоба отклонена')
                            ->success()
                            ->send();
                    }),

                // Просмотр объявления
                Action::make('view_ad')
                    ->label('Объявление')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Complaint $record): string =>
                        AdResource::getUrl('view', ['record' => $record->ad_id])
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Массовое разрешение
                    BulkAction::make('bulk_resolve')
                        ->label('Разрешить выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Textarea::make('resolution_note')
                                ->label('Комментарий к решению')
                                ->required()
                                ->rows(3),

                            Forms\Components\Toggle::make('block_ads')
                                ->label('Заблокировать объявления')
                                ->default(false),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data): void {
                            $complaintIds = $records->pluck('id')->toArray();
                            $resolved = app(ComplaintModerationService::class)->bulkResolve(
                                $complaintIds,
                                auth()->user(),
                                $data['resolution_note'],
                                $data['block_ads'] ?? false
                            );

                            Notification::make()
                                ->title("Разрешено жалоб: {$resolved}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Массовое отклонение
                    BulkAction::make('bulk_reject')
                        ->label('Отклонить выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('resolution_note')
                                ->label('Причина отклонения')
                                ->required()
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data): void {
                            $complaintIds = $records->pluck('id')->toArray();
                            $rejected = app(ComplaintModerationService::class)->bulkReject(
                                $complaintIds,
                                auth()->user(),
                                $data['resolution_note']
                            );

                            Notification::make()
                                ->title("Отклонено жалоб: {$rejected}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplaints::route('/'),
            'view' => Pages\ViewComplaint::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ad', 'user', 'resolver']);
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Complaint::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Complaint::class);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user() && auth()->user()->role->isAdmin();
    }
}
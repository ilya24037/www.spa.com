<?php

namespace App\Filament\Resources\ComplaintResource\Pages;

use App\Filament\Resources\ComplaintResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewComplaint extends ViewRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resolve')
                ->label('Разрешить')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->isPending())
                ->form([
                    \Filament\Forms\Components\Textarea::make('resolution_note')
                        ->label('Комментарий к решению')
                        ->required()
                        ->rows(3),

                    \Filament\Forms\Components\Toggle::make('block_ad')
                        ->label('Заблокировать объявление')
                        ->default(true),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $this->record->resolve(auth()->user(), $data['resolution_note']);

                    if ($data['block_ad'] ?? false) {
                        $this->record->ad->update([
                            'status' => \App\Enums\AdStatus::BLOCKED->value,
                            'moderation_comment' => 'Заблокировано по результатам рассмотрения жалобы #' . $this->record->id,
                        ]);
                    }

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            Actions\Action::make('reject')
                ->label('Отклонить')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->isPending())
                ->form([
                    \Filament\Forms\Components\Textarea::make('resolution_note')
                        ->label('Причина отклонения')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $this->record->reject(auth()->user(), $data['resolution_note']);
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Информация о жалобе')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID жалобы'),

                        Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'resolved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Ожидает рассмотрения',
                                'resolved' => 'Разрешена',
                                'rejected' => 'Отклонена',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Дата подачи')
                            ->dateTime('d.m.Y H:i'),

                        Infolists\Components\TextEntry::make('resolved_at')
                            ->label('Дата решения')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Детали жалобы')
                    ->schema([
                        Infolists\Components\TextEntry::make('ad.title')
                            ->label('Объявление')
                            ->url(fn () =>
                                \App\Filament\Resources\AdResource::getUrl('view', ['record' => $this->record->ad_id])
                            )
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Заявитель')
                            ->description(fn () => $this->record->user->email),

                        Infolists\Components\TextEntry::make('reason')
                            ->label('Причина жалобы')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Решение')
                    ->schema([
                        Infolists\Components\TextEntry::make('resolver.name')
                            ->label('Администратор')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('resolution_note')
                            ->label('Комментарий администратора')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn () => !$this->record->isPending()),
            ]);
    }
}
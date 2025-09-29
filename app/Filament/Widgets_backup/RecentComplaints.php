<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Complaint;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentComplaints extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $heading = 'Последние жалобы';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Complaint::query()
                    ->with(['ad', 'user'])
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('ad.title')
                    ->label('Объявление')
                    ->limit(25)
                    ->tooltip(function (Complaint $record): string {
                        return $record->ad->title ?? '';
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Заявитель')
                    ->limit(20),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(30)
                    ->wrap(),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Рассмотреть')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Complaint $record): string =>
                        \App\Filament\Resources\ComplaintResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->paginated(false);
    }
}
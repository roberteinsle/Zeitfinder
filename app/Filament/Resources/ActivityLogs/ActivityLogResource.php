<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $slug = 'protokoll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'action';

    protected static ?string $modelLabel = 'Protokolleintrag';

    protected static ?string $pluralModelLabel = 'Aktivitätsprotokoll';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('Zeit'))->dateTime('d.m.Y H:i:s', 'Europe/Berlin')->sortable(),
                TextColumn::make('action')->label(__('Aktion'))->searchable()->badge(),
                TextColumn::make('user.email')->label(__('Benutzer'))->placeholder('–')->searchable(),
                TextColumn::make('subject_type')->label(__('Objekt'))
                    ->formatStateUsing(fn (ActivityLog $record) => $record->subject_type.' #'.$record->subject_id)->placeholder('–'),
                TextColumn::make('properties')->label(__('Details'))
                    ->formatStateUsing(fn (ActivityLog $record) => json_encode($record->properties, JSON_UNESCAPED_UNICODE) ?: '')
                    ->limit(80)->wrap(),
                TextColumn::make('ip_address')->label(__('IP'))->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivityLogs::route('/'),
        ];
    }
}

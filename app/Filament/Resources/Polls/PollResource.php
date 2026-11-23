<?php

namespace App\Filament\Resources\Polls;

use App\Filament\Resources\Polls\Pages\ManagePolls;
use App\Modules\Polls\Actions\DeletePoll;
use App\Modules\Polls\Enums\PollStatus;
use App\Modules\Polls\Models\Poll;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PollResource extends Resource
{
    protected static ?string $model = Poll::class;

    protected static ?string $slug = 'umfragen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'Umfrage';

    protected static ?string $pluralModelLabel = 'Umfragen';

    protected static ?int $navigationSort = 10;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')->label(__('Titel')),
            TextEntry::make('status')->label(__('Status'))->formatStateUsing(fn (PollStatus $state) => $state->label())->badge(),
            TextEntry::make('organizer_name')->label(__('Organisiert von')),
            TextEntry::make('organizer_email')->label(__('E-Mail')),
            TextEntry::make('location')->label(__('Ort'))->placeholder('–'),
            TextEntry::make('description')->label(__('Beschreibung'))->placeholder('–')->columnSpanFull(),
            TextEntry::make('options_count')->label(__('Termine'))->state(fn (Poll $record) => $record->options()->count()),
            TextEntry::make('participants_count')->label(__('Teilnahmen'))->state(fn (Poll $record) => $record->participants()->count()),
            TextEntry::make('created_at')->label(__('Erstellt'))->dateTime('d.m.Y H:i', 'Europe/Berlin'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['participants', 'options']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('Titel'))->searchable()->limit(50),
                TextColumn::make('organizer_name')->label(__('Organisiert von'))->searchable(),
                TextColumn::make('organizer_email')->label(__('E-Mail'))->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn (PollStatus $state) => $state->label())
                    ->color(fn (PollStatus $state) => $state === PollStatus::Finalized ? 'success' : 'primary'),
                TextColumn::make('options_count')->label(__('Termine'))->sortable(),
                TextColumn::make('participants_count')->label(__('Teilnahmen'))->sortable(),
                TextColumn::make('created_at')->label(__('Erstellt'))->dateTime('d.m.Y H:i', 'Europe/Berlin')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options([
                    PollStatus::Open->value => PollStatus::Open->label(),
                    PollStatus::Finalized->value => PollStatus::Finalized->label(),
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('open')->label(__('Öffnen'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Poll $record) => route('polls.show', $record), shouldOpenInNewTab: true),
                DeleteAction::make()->using(function (Poll $record): bool {
                    app(DeletePoll::class)($record);

                    return true;
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePolls::route('/'),
        ];
    }
}

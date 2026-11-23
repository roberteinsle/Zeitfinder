<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Modules\Accounts\Actions\DeleteUser;
use App\Modules\Accounts\Actions\SetUserAdmin;
use App\Modules\Accounts\Actions\SetUserBlocked;
use App\Modules\Accounts\Actions\UpdateProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'benutzer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = 'Benutzer';

    protected static ?string $pluralModelLabel = 'Benutzer';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(80),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('passkeys'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('E-Mail'))->searchable()->sortable(),
                IconColumn::make('is_admin')->label(__('Admin'))->boolean(),
                TextColumn::make('passkeys_count')->label(__('Passkeys'))->sortable(),
                IconColumn::make('two_factor_confirmed_at')->label(__('Passwort + 2FA'))->boolean()
                    ->getStateUsing(fn (User $record) => $record->hasPasswordLogin()),
                TextColumn::make('last_login_at')->label(__('Letzte Anmeldung'))->dateTime('d.m.Y H:i', 'Europe/Berlin')->sortable(),
                TextColumn::make('blocked_at')->label(__('Gesperrt seit'))->dateTime('d.m.Y H:i', 'Europe/Berlin')->placeholder('–'),
                TextColumn::make('created_at')->label(__('Registriert'))->dateTime('d.m.Y', 'Europe/Berlin')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label(__('Admin')),
                TernaryFilter::make('blocked_at')->label(__('Gesperrt'))->nullable(),
            ])
            ->recordActions([
                EditAction::make()->using(function (User $record, array $data): Model {
                    app(UpdateProfile::class)($record, (string) $data['name']);

                    return $record;
                }),
                Action::make('block')
                    ->label(fn (User $record) => $record->isBlocked() ? __('Entsperren') : __('Sperren'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn (User $record) => $record->is(auth()->user()))
                    ->action(fn (User $record) => app(SetUserBlocked::class)(self::actor(), $record, ! $record->isBlocked())),
                Action::make('admin')
                    ->label(fn (User $record) => $record->is_admin ? __('Admin entziehen') : __('Zum Admin machen'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->requiresConfirmation()
                    ->hidden(fn (User $record) => $record->is(auth()->user()))
                    ->action(fn (User $record) => app(SetUserAdmin::class)(self::actor(), $record, ! $record->is_admin)),
                DeleteAction::make()->using(function (User $record): bool {
                    app(DeleteUser::class)(self::actor(), $record);

                    return true;
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    private static function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}

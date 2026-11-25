<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Modules\Legal\Actions\UpdateLegalSettings;
use App\Modules\Legal\Models\LegalSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class LegalSettings extends Page
{
    protected static ?string $slug = 'rechtliches';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Rechtliches';

    protected static ?string $title = 'Impressum und Datenschutz';

    protected static ?int $navigationSort = 95;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', LegalSetting::current()) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(LegalSetting::current()->only(LegalSetting::FIELDS));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Betreiber'))
                    ->description(__('Erscheint im Impressum und als Verantwortlicher in der Datenschutzerklärung. Das Impressum ist erst sichtbar, wenn Name, Anschrift und E-Mail ausgefüllt sind.'))
                    ->schema([
                        TextInput::make('operator_name')->label(__('Name'))->maxLength(120),
                        Textarea::make('operator_address')->label(__('Anschrift'))->maxLength(500)->rows(3)
                            ->helperText(__('Ladungsfähige Anschrift, kein Postfach.')),
                        TextInput::make('contact_email')->label(__('E-Mail'))->email()->maxLength(255),
                        TextInput::make('contact_phone')->label(__('Telefon'))->maxLength(50),
                        Textarea::make('imprint_extra')->label(__('Weitere Angaben im Impressum'))->maxLength(5000)->rows(4),
                    ]),
                Section::make(__('Datenschutzerklärung'))
                    ->schema([
                        Textarea::make('hosting_provider')->label(__('Hoster'))->maxLength(500)->rows(4)
                            ->helperText(__('Name und Anschrift des Unternehmens, bei dem der Server steht.')),
                        Textarea::make('processors')->label(__('Weitere Dienstleister'))->maxLength(5000)->rows(6)
                            ->helperText(__('Zum Beispiel Mailversand und vorgeschalteter Schutz, jeweils mit Zweck und Hinweis auf Drittländer.')),
                        Textarea::make('supervisory_authority')->label(__('Aufsichtsbehörde'))->maxLength(500)->rows(4),
                        Textarea::make('privacy_extra')->label(__('Weitere Angaben'))->maxLength(5000)->rows(4),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('Speichern'))->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        app(UpdateLegalSettings::class)($user, $this->form->getState());

        Notification::make()->title(__('Gespeichert'))->success()->send();
    }
}

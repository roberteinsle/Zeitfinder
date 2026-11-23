<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Modules\Seo\Actions\IndexNowResult;
use App\Modules\Seo\Actions\SubmitToIndexNow;
use App\Modules\Seo\Actions\UpdateSeoSettings;
use App\Modules\Seo\Models\SeoSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class SeoSettings extends Page
{
    protected static ?string $slug = 'seo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'SEO';

    protected static ?string $title = 'SEO';

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', SeoSetting::current()) ?? false;
    }

    public function mount(): void
    {
        $settings = SeoSetting::current();

        $this->form->fill($settings->only([
            'site_name', 'home_title', 'home_description', 'default_description', 'organization_name',
            'indexing_enabled', 'robots_extra', 'llms_intro', 'indexnow_enabled',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        $defaults = SeoSetting::DEFAULTS;

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Startseite'))
                    ->description(__('Leere Felder verwenden den Standardtext (als Platzhalter angezeigt).'))
                    ->schema([
                        TextInput::make('home_title')->label(__('Meta-Titel'))->maxLength(70)
                            ->placeholder($defaults['home_title'])->helperText(__('Höchstens 70 Zeichen; erscheint als Titel im Suchergebnis.')),
                        Textarea::make('home_description')->label(__('Meta-Beschreibung'))->maxLength(160)->rows(3)
                            ->placeholder($defaults['home_description'])->helperText(__('Höchstens 160 Zeichen; erscheint als Text unter dem Titel.')),
                    ]),
                Section::make(__('Alle Seiten'))
                    ->schema([
                        TextInput::make('site_name')->label(__('Name der Website'))->maxLength(60)
                            ->placeholder($defaults['site_name'])->helperText(__('Wird an Seitentitel angehängt („Neue Umfrage · Zeitfinder“).')),
                        Textarea::make('default_description')->label(__('Standard-Beschreibung'))->maxLength(160)->rows(2)
                            ->placeholder($defaults['default_description']),
                        TextInput::make('organization_name')->label(__('Betreiber (Schema.org)'))->maxLength(100)
                            ->helperText(__('Optional. Wird als Organization in den strukturierten Daten der Startseite ausgegeben.')),
                    ]),
                Section::make(__('Suchmaschinen und KI-Crawler'))
                    ->schema([
                        Toggle::make('indexing_enabled')->label(__('Indexierung erlauben'))
                            ->helperText(__('Aus: alle Seiten „noindex“, robots.txt sperrt alles, Sitemap und llms.txt sind leer.')),
                        Textarea::make('robots_extra')->label(__('Zusätzliche Regeln für robots.txt'))->maxLength(2000)->rows(4)
                            ->placeholder("Disallow: /beispiel\nUser-agent: GPTBot\nDisallow: /")
                            ->helperText(__('Wird unter die Standardregeln gesetzt.')),
                        Textarea::make('llms_intro')->label(__('Einleitung für llms.txt'))->maxLength(2000)->rows(4)
                            ->helperText(__('Optional. Markdown; beschreibt die Website für Sprachmodelle.')),
                    ]),
                Section::make('IndexNow')
                    ->schema([
                        Toggle::make('indexnow_enabled')->label(__('IndexNow verwenden'))
                            ->helperText(__('Meldet die öffentlichen Seiten nach dem Speichern an Bing, Yandex & Co.')),
                        Text::make(fn () => $this->indexNowStatus()),
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

        app(UpdateSeoSettings::class)($this->actor(), $this->form->getState());

        Notification::make()->title(__('Gespeichert'))->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('indexnow')
                ->label(__('An IndexNow senden'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalDescription(__('Meldet alle öffentlichen Seiten aus der Sitemap an IndexNow.'))
                ->disabled(fn () => ! SeoSetting::current()->indexnow_enabled)
                ->action(function () {
                    abort_unless(self::canAccess(), 403);
                    $result = app(SubmitToIndexNow::class)();

                    Notification::make()
                        ->title($result->message())
                        ->status($result === IndexNowResult::Submitted ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }

    private function indexNowStatus(): string
    {
        $settings = SeoSetting::current();
        $keyUrl = route('seo.indexnow-key', ['key' => $settings->indexnow_key]);

        if ($settings->indexnow_submitted_at === null) {
            return __('Schlüsseldatei: :url · noch nichts gemeldet.', ['url' => $keyUrl]);
        }

        return __('Schlüsseldatei: :url · zuletzt gemeldet am :time (HTTP :status).', [
            'url' => $keyUrl,
            'time' => $settings->indexnow_submitted_at->timezone('Europe/Berlin')->format('d.m.Y H:i'),
            'status' => $settings->indexnow_status,
        ]);
    }

    private function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}

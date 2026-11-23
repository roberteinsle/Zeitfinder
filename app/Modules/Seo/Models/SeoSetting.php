<?php

namespace App\Modules\Seo\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * SEO settings of the public site, edited in the admin panel. A single row (id = 1).
 *
 * @property int $id
 * @property string|null $site_name
 * @property string|null $home_title
 * @property string|null $home_description
 * @property string|null $default_description
 * @property string|null $organization_name
 * @property bool $indexing_enabled
 * @property string|null $robots_extra
 * @property string|null $llms_intro
 * @property bool $indexnow_enabled
 * @property string $indexnow_key
 * @property CarbonImmutable|null $indexnow_submitted_at
 * @property int|null $indexnow_status
 */
class SeoSetting extends Model
{
    public const DEFAULTS = [
        'site_name' => 'Zeitfinder',
        'home_title' => 'Zeitfinder – Terminumfrage ohne Hin und Her',
        'home_description' => 'Kostenlose Terminumfrage ohne Konto und ohne Werbung: Termine vorschlagen, Link teilen, alle stimmen mit Ja, Nein oder „Wenn nötig“ ab.',
        'default_description' => 'Zeitfinder: Termine vorschlagen, Link teilen und gemeinsam den passenden Termin finden.',
    ];

    /** Editable fields; IndexNow key and status are internal and set via forceFill. */
    protected $fillable = [
        'site_name', 'home_title', 'home_description', 'default_description', 'organization_name',
        'indexing_enabled', 'robots_extra', 'llms_intro', 'indexnow_enabled',
    ];

    protected function casts(): array
    {
        return [
            'indexing_enabled' => 'boolean',
            'indexnow_enabled' => 'boolean',
            'indexnow_submitted_at' => 'immutable_datetime',
            'indexnow_status' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->find(1) ?? self::query()->forceCreate([
            'id' => 1,
            'indexnow_key' => bin2hex(random_bytes(16)),
        ])->refresh();
    }

    public function siteName(): string
    {
        return $this->site_name ?: self::DEFAULTS['site_name'];
    }

    public function homeTitle(): string
    {
        return $this->home_title ?: self::DEFAULTS['home_title'];
    }

    public function homeDescription(): string
    {
        return $this->home_description ?: self::DEFAULTS['home_description'];
    }

    public function defaultDescription(): string
    {
        return $this->default_description ?: self::DEFAULTS['default_description'];
    }
}

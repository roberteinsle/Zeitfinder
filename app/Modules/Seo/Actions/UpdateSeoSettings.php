<?php

namespace App\Modules\Seo\Actions;

use App\Models\User;
use App\Modules\Seo\Models\SeoSetting;
use App\Support\ActivityLogger;

final class UpdateSeoSettings
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly SubmitToIndexNow $indexNow,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated form data
     */
    public function __invoke(User $actor, array $data): SeoSetting
    {
        $settings = SeoSetting::current();

        $settings->fill([
            'site_name' => self::text($data['site_name'] ?? null),
            'home_title' => self::text($data['home_title'] ?? null),
            'home_description' => self::text($data['home_description'] ?? null),
            'default_description' => self::text($data['default_description'] ?? null),
            'organization_name' => self::text($data['organization_name'] ?? null),
            'indexing_enabled' => (bool) ($data['indexing_enabled'] ?? true),
            'robots_extra' => self::text($data['robots_extra'] ?? null),
            'llms_intro' => self::text($data['llms_intro'] ?? null),
            'indexnow_enabled' => (bool) ($data['indexnow_enabled'] ?? false),
        ]);

        $changed = array_keys($settings->getDirty());
        $settings->save();

        if ($changed !== []) {
            $this->activity->log('seo.updated', $settings, ['fields' => $changed], $actor->id);

            // Changed texts change the public pages: tell search engines after the response.
            if ($settings->indexnow_enabled && $settings->indexing_enabled) {
                defer(fn () => ($this->indexNow)());
            }
        }

        return $settings;
    }

    private static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';

        return $value === '' ? null : $value;
    }
}

<?php

namespace App\Modules\Legal\Actions;

use App\Models\User;
use App\Modules\Legal\Models\LegalSetting;
use App\Support\ActivityLogger;

final class UpdateLegalSettings
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  array<string, mixed>  $data  validated form data
     */
    public function __invoke(User $actor, array $data): LegalSetting
    {
        $settings = LegalSetting::current();

        foreach (LegalSetting::FIELDS as $field) {
            $value = is_string($data[$field] ?? null) ? trim(str_replace("\r\n", "\n", $data[$field])) : '';
            $settings->setAttribute($field, $value === '' ? null : $value);
        }

        $changed = array_keys($settings->getDirty());
        $settings->save();

        if ($changed !== []) {
            $this->activity->log('legal.updated', $settings, ['fields' => $changed], $actor->id);
        }

        return $settings;
    }
}

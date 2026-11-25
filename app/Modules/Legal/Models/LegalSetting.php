<?php

namespace App\Modules\Legal\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Operator details for the imprint and the privacy page. A single row (id = 1).
 *
 * @property int $id
 * @property string|null $operator_name
 * @property string|null $operator_address
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $imprint_extra
 * @property string|null $hosting_provider
 * @property string|null $processors
 * @property string|null $supervisory_authority
 * @property string|null $privacy_extra
 */
class LegalSetting extends Model
{
    public const FIELDS = [
        'operator_name', 'operator_address', 'contact_email', 'contact_phone', 'imprint_extra',
        'hosting_provider', 'processors', 'supervisory_authority', 'privacy_extra',
    ];

    protected $fillable = self::FIELDS;

    public static function current(): self
    {
        return self::query()->find(1) ?? self::query()->forceCreate(['id' => 1])->refresh();
    }

    /** An incomplete imprint is not shown at all. */
    public function hasOperator(): bool
    {
        return filled($this->operator_name) && filled($this->operator_address) && filled($this->contact_email);
    }
}

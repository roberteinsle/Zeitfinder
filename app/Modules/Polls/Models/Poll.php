<?php

namespace App\Modules\Polls\Models;

use App\Models\User;
use App\Modules\Polls\Enums\PollStatus;
use Database\Factories\PollFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property string $admin_token_hash
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property string $organizer_name
 * @property string $organizer_email
 * @property bool $allow_maybe
 * @property bool $notify_on_vote
 * @property PollStatus $status
 * @property int|null $final_option_id
 * @property Carbon|null $finalized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PollOption> $options
 * @property-read Collection<int, Participant> $participants
 * @property-read PollOption|null $finalOption
 * @property-read User|null $user
 */
#[UseFactory(PollFactory::class)]
class Poll extends Model
{
    /** @use HasFactory<PollFactory> */
    use HasFactory;

    /**
     * public_id, user_id, admin_token_hash, status and final_* are internal (forceFill).
     *
     * @var list<string>
     */
    protected $fillable = ['title', 'description', 'location', 'organizer_name', 'organizer_email', 'allow_maybe', 'notify_on_vote'];

    protected $hidden = ['admin_token_hash'];

    protected $attributes = [
        'status' => 'open',
        'allow_maybe' => true,
        'notify_on_vote' => false,
    ];

    protected function casts(): array
    {
        return [
            'allow_maybe' => 'boolean',
            'notify_on_vote' => 'boolean',
            'status' => PollStatus::class,
            'finalized_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return HasMany<PollOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * @return HasMany<Participant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<PollOption, $this>
     */
    public function finalOption(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'final_option_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->status === PollStatus::Open;
    }

    public function isFinalized(): bool
    {
        return $this->status === PollStatus::Finalized;
    }
}

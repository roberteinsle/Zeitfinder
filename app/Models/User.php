<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property bool $is_admin
 * @property Carbon|null $blocked_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 */
class User extends Authenticatable implements FilamentUser, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * Internal fields (is_admin, blocked_at, password, two_factor_*) are only set via forceFill.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email'];

    /**
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // The passkey requirement for the session is enforced by EnsurePasskeySession.
        return $panel->getId() === 'admin' && $this->is_admin && ! $this->isBlocked();
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function hasPasswordLogin(): bool
    {
        return $this->password !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Name to suggest where others will see it. New accounts are named after the local part
     * of the address; that must not be published without the user choosing it.
     */
    public function displayNameForOthers(): ?string
    {
        return mb_strtolower($this->name) === mb_strtolower(Str::limit(Str::before($this->email, '@'), 80, '')) ? null : $this->name;
    }
}

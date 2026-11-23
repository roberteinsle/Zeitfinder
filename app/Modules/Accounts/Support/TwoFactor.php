<?php

namespace App\Modules\Accounts\Support;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP (RFC 6238) for password logins.
 */
final class TwoFactor
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function otpauthUrl(string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl((string) config('app.name'), $email, $secret);
    }

    /**
     * SVG markup generated locally (no external QR service).
     */
    public function qrCodeSvg(string $email, string $secret): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($this->otpauthUrl($email, $secret));
    }

    public function verifyForSetup(string $secret, string $code): bool
    {
        if (! self::isWellFormed($code)) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Verifies a code for a user and rejects codes that were already used (replay).
     */
    public function verifyForUser(User $user, string $code): bool
    {
        $secret = $user->two_factor_secret;

        if ($secret === null || ! self::isWellFormed($code)) {
            return false;
        }

        $key = 'two-factor-last:'.$user->id;
        $last = Cache::get($key);

        $timestamp = $this->google2fa->verifyKeyNewer($secret, $code, is_int($last) ? $last : null, 1);

        if ($timestamp === false) {
            return false;
        }

        Cache::put($key, $timestamp === true ? $this->google2fa->getTimestamp() : $timestamp, now()->addMinutes(5));

        return true;
    }

    public static function isWellFormed(string $code): bool
    {
        return preg_match('/^\d{6}$/D', $code) === 1;
    }
}

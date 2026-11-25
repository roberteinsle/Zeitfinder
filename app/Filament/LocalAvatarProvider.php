<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials as an inline SVG. Filament's default sends the initials to ui-avatars.com,
 * which the CSP blocks and our privacy rules forbid.
 */
final class LocalAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', Filament::getNameForDefaultAvatar($record), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = array_map(fn (string $word) => mb_substr($word, 0, 1), count($words) > 1 ? [$words[0], end($words)] : $words);
        $text = e(mb_strtoupper(implode('', $initials)) ?: '?');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#1d2433"/>'
            .'<text x="32" y="32" dy=".36em" fill="#fff" font-family="sans-serif" font-size="26" font-weight="600" text-anchor="middle">'.$text.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

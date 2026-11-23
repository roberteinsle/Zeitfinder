<?php

namespace App\Support;

final class EmailAddress
{
    /**
     * Server-side validation rules for every email field.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }

    public static function normalize(mixed $email): string
    {
        return is_string($email) ? mb_strtolower(trim($email)) : '';
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

class Credential
{
    public static function matches(string $plainText, string $stored): bool
    {
        if (self::isHashed($stored)) {
            return Hash::check($plainText, $stored);
        }

        return hash_equals($stored, $plainText);
    }

    public static function isHashed(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$argon2i$')
            || str_starts_with($value, '$argon2id$');
    }
}

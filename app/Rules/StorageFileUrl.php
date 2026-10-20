<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A URL pointing at a file previously uploaded through /api/files/upload. */
class StorageFileUrl implements ValidationRule
{
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value) || $value === '' || strlen($value) > 2048 || str_contains($value, '..')) {
            return false;
        }

        if (str_starts_with($value, '/storage/uploads/')) {
            return true;
        }

        $base = rtrim((string) config('app.url'), '/').'/storage/uploads/';

        return str_starts_with($value, $base);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isValid($value)) {
            $fail('The :attribute must be a valid uploaded file URL.');
        }
    }
}

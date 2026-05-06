<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Validation;

use S2J\Similarity\Contracts\Errors\ValidationError;

final class RequestValidator
{
    /**
     * @param array<string, mixed> $body
     */
    public static function requireNonEmptyString(array $body, string $field): string
    {
        $v = $body[$field] ?? null;
        if (!is_string($v)) {
            throw new ValidationError("{$field} must be a string.", ['field' => $field]);
        }
        $t = trim($v);
        if ($t === '') {
            throw new ValidationError("{$field} must not be empty.", ['field' => $field]);
        }
        return $t;
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function optionalString(array $body, string $field): ?string
    {
        if (!array_key_exists($field, $body)) return null;
        $v = $body[$field];
        if ($v === null) return null;
        if (!is_string($v)) {
            throw new ValidationError("{$field} must be a string or null.", ['field' => $field]);
        }
        $t = trim($v);
        return $t !== '' ? $t : null;
    }
}

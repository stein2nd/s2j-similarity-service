<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Error;

use S2J\Similarity\Contracts\Errors\DomainError;

final class ErrorMapper
{
    /**
     * @return array{status:int, body:array{error:array{type:string,message:string,details:object|array|null}}, headers:array<string, string>}
     */
    public static function toErrorResponse(DomainError $e): array
    {
        $type = $e->type;
        $status = self::httpStatus($type);

        $details = $e->details;
        if ($details === []) {
            $details = null;
        }

        return [
            'status' => $status,
            'body' => [
                'error' => [
                    'type' => $type,
                    'message' => $e->getMessage(),
                    'details' => $details,
                ],
            ],
            'headers' => self::errorHeaders($e),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function errorHeaders(DomainError $e): array
    {
        if ($e->type !== 'rate_limit') {
            return [];
        }

        $retry = $e->details['retry_after'] ?? null;
        if (is_int($retry) && $retry >= 0) {
            return ['Retry-After' => (string) $retry];
        }

        return [];
    }

    private static function httpStatus(string $type): int
    {
        return match ($type) {
            'validation_error' => 400,
            'auth_error' => 401,
            'permission_error' => 403,
            'not_found' => 404,
            'timeout' => 408,
            'rate_limit' => 429,
            'provider_error' => 503,
            'network_error' => 503,
            'internal_error' => 500,
            default => 500,
        };
    }
}

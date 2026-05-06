<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Error;

use S2J\Similarity\Contracts\Errors\DomainError;

final class ErrorMapper
{
    /**
     * @return array{status:int, body:array{error:array{type:string,message:string,details:object|array|null}}}
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
        ];
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
            'provider_error' => 502,
            'network_error' => 503,
            'internal_error' => 500,
            default => 500,
        };
    }
}

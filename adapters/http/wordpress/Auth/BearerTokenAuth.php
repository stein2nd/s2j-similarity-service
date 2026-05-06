<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Auth;

use S2J\Similarity\Adapters\Http\WordPress\Http\RequestReader;

final class BearerTokenAuth
{
    public function __construct(
        private readonly ?string $expectedToken
    ) {}

    /**
     * @return true|object
     */
    public function permission(mixed $request): mixed
    {
        if ($this->expectedToken === null || $this->expectedToken === '') {
            return true;
        }

        $authHeader = RequestReader::header($request, 'authorization')
            ?? RequestReader::header($request, 'Authorization');

        $token = self::parseBearer($authHeader);
        if ($token === null) {
            return self::wpError('auth_error', 'Unauthorized', 401);
        }

        if (!hash_equals($this->expectedToken, $token)) {
            return self::wpError('auth_error', 'Unauthorized', 401);
        }

        return true;
    }

    private static function parseBearer(?string $header): ?string
    {
        if ($header === null) return null;
        $h = trim($header);
        if ($h === '') return null;

        if (stripos($h, 'Bearer ') !== 0) return null;
        $token = trim(substr($h, 7));
        return $token !== '' ? $token : null;
    }

    /**
     * @return object
     */
    private static function wpError(string $type, string $message, int $status): object
    {
        if (class_exists('WP_Error')) {
            /** @var class-string $cls */
            $cls = 'WP_Error';
            return new $cls($type, $message, ['status' => $status]);
        }

        // Non-WordPress environments: return an opaque object.
        return (object)[
            'code' => $type,
            'message' => $message,
            'data' => ['status' => $status],
        ];
    }
}

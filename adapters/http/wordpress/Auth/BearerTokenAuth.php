<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Auth;

use S2J\Similarity\Adapters\Http\WordPress\Http\RequestReader;

final class BearerTokenAuth
{
    /**
     * @param callable|null $authorizeAuthenticated Invoked after bearer verification succeeds (or when no bearer is configured).
     *                                              Must return true, or a {@see WP_Error} / opaque error object for denial (typically HTTP 403).
     */
    public function __construct(
        private readonly ?string $expectedToken,
        private $authorizeAuthenticated = null,
    ) {}

    /**
     * Build a permission denial response for use inside {@see $authorizeAuthenticated} callbacks.
     *
     * @return object|\WP_Error
     */
    public static function permissionDenied(string $message = 'Forbidden'): object
    {
        return self::wpError('permission_error', $message, 403);
    }

    /**
     * @return true|object
     */
    public function permission(mixed $request): mixed
    {
        if ($this->expectedToken === null || $this->expectedToken === '') {
            return $this->invokeAuthorizeAuthenticated($request);
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

        return $this->invokeAuthorizeAuthenticated($request);
    }

    /**
     * @return true|object
     */
    private function invokeAuthorizeAuthenticated(mixed $request): mixed
    {
        if ($this->authorizeAuthenticated === null) {
            return true;
        }

        $result = ($this->authorizeAuthenticated)($request);

        return $result === true ? true : $result;
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
     * @return object|\WP_Error
     */
    private static function wpError(string $type, string $message, int $status): object
    {
        if (class_exists(\WP_Error::class)) {
            return new \WP_Error($type, $message, ['status' => $status]);
        }

        // Non-WordPress environments: return an opaque object.
        return (object)[
            'code' => $type,
            'message' => $message,
            'data' => ['status' => $status],
        ];
    }
}

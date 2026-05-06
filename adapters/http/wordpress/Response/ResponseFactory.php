<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Response;

final class ResponseFactory
{
    /**
     * @param array<string, mixed> $body
     * @return mixed
     */
    public static function json(array $body, int $status = 200): mixed
    {
        if (function_exists('rest_ensure_response')) {
            $resp = \rest_ensure_response($body);
            if (is_object($resp) && method_exists($resp, 'set_status')) {
                $resp->set_status($status);
            }
            return $resp;
        }

        // Non-WordPress environments: return plain array with status for tests.
        return [
            'status' => $status,
            'body' => $body,
        ];
    }

    /**
     * @param array<string, mixed> $body
     * @return mixed
     */
    public static function ok(array $body): mixed
    {
        return self::json($body, 200);
    }
}

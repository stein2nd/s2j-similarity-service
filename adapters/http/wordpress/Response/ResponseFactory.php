<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Response;

final class ResponseFactory
{
    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     * @return mixed
     */
    public static function json(array $body, int $status = 200, array $headers = []): mixed
    {
        if (function_exists('rest_ensure_response')) {
            $resp = \rest_ensure_response($body);
            if (is_object($resp) && method_exists($resp, 'set_status')) {
                $resp->set_status($status);
            }
            foreach ($headers as $name => $value) {
                if (is_object($resp) && method_exists($resp, 'header')) {
                    $resp->header((string) $name, (string) $value);
                }
            }

            return $resp;
        }

        // Non-WordPress environments: return plain array with status for tests.
        return [
            'status' => $status,
            'body' => $body,
            'headers' => $headers,
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

<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Http;

final class RequestReader
{
    /**
     * @return array<string, mixed>
     */
    public static function jsonBody(mixed $request): array
    {
        if (is_object($request) && method_exists($request, 'get_json_params')) {
            $params = $request->get_json_params();
            return is_array($params) ? $params : [];
        }

        return [];
    }

    public static function header(mixed $request, string $name): ?string
    {
        if (is_object($request) && method_exists($request, 'get_header')) {
            $v = $request->get_header($name);
            if (is_string($v) && $v !== '') return $v;
        }

        return null;
    }
}

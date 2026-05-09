<?php

namespace S2J\Similarity\Adapters\Http\WordPress;

use S2J\Similarity\Adapters\Http\WordPress\Controllers\EmbeddingController;
use S2J\Similarity\Adapters\Http\WordPress\Controllers\SimilarityController;

/**
 * HTTP runtime uses WordPress REST API only ({@see register_rest_route}).
 *
 * OpenAPI logical paths map to this namespace; see
 * docs/interfaces/rest_api_spec.md § REST API (HTTP Runtime / WordPress REST Adapter).
 */
final class Routes
{
    public const DEFAULT_NAMESPACE = 's2j/v1';

    /**
     * Register routes to WordPress REST API.
     *
     * This is intentionally free of WordPress type hints so that the library can
     * be installed in non-WordPress environments without autoload failures.
     *
     * @param callable|null $registerRoute function(string $namespace, string $route, array $args): mixed
     */
    public static function register(
        SimilarityController $similarityController,
        EmbeddingController $embeddingController,
        ?callable $registerRoute = null,
        string $namespace = self::DEFAULT_NAMESPACE
    ): void {
        $registerRoute ??= static function (string $ns, string $route, array $args): void {
            if (!function_exists('register_rest_route')) {
                throw new \RuntimeException('register_rest_route() is not available. Are you running inside WordPress?');
            }
            \register_rest_route($ns, $route, $args);
        };

        $registerRoute($namespace, '/similarity', [
            'methods' => 'POST',
            'callback' => [$similarityController, 'handle'],
            'permission_callback' => [$similarityController, 'permission'],
        ]);

        $registerRoute($namespace, '/embedding', [
            'methods' => 'POST',
            'callback' => [$embeddingController, 'handle'],
            'permission_callback' => [$embeddingController, 'permission'],
        ]);
    }
}


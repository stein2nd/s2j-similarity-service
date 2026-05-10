<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Adapters\Http\WordPress\Auth\BearerTokenAuth;
use S2J\Similarity\Adapters\Http\WordPress\Controllers\EmbeddingController;
use S2J\Similarity\Adapters\Http\WordPress\Controllers\SimilarityController;
use S2J\Similarity\Adapters\Http\WordPress\Routes;
use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Application\SimilarityService as AppSimilarityService;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Contracts\Errors\AuthorizationError;
use S2J\Similarity\Contracts\Errors\ProviderError;
use S2J\Similarity\Contracts\Errors\RateLimitError;
use S2J\Similarity\Contracts\Errors\TimeoutError;
use S2J\Similarity\Tests\Support\OpenApiResponseContractValidator;

/**
 * HTTP integration tests through WordPress {@see WP_REST_Server} (WorDBless runtime).
 *
 * Aligns with docs/interfaces/rest_api_spec.md § HTTP integration test (WordPress REST API Adapter)
 * and § REST API (HTTP Runtime / WordPress REST Adapter).
 */
final class MutableAuthorizationGate
{
    public bool $allowAuthenticated = true;

    public function __invoke(mixed $request): mixed
    {
        if ($this->allowAuthenticated) {
            return true;
        }

        return BearerTokenAuth::permissionDenied('Insufficient permission');
    }
}

final class WordPressRestAdapterIntegrationTest extends TestCase
{
    private const INTEGRATION_TOKEN = 'integration-test-token';

    private static ControllableEmbeddingStrategy $strategy;

    private static MutableAuthorizationGate $permissionGate;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$strategy = new ControllableEmbeddingStrategy();
        self::$permissionGate = new MutableAuthorizationGate();

        $auth = new BearerTokenAuth(self::INTEGRATION_TOKEN, self::$permissionGate);
        $similarityController = new SimilarityController(
            new AppSimilarityService(self::$strategy),
            $auth
        );
        $embeddingController = new EmbeddingController(
            new EmbeddingService(
                strategy: self::$strategy,
                provider: 'test-provider',
                defaultModel: 'test-model'
            ),
            $auth
        );

        Routes::register($similarityController, $embeddingController);
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$strategy->throwOnEmbed = null;
        self::$strategy->vector = [1.0, 0.0];
        self::$permissionGate->allowAuthenticated = true;
    }

    public function testSimilarityRouteRegisteredWithPostMethod(): void
    {
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/s2j/v1/similarity', $routes);
        $methods = array_merge(...array_map(
            static fn (array $h): array => array_keys($h['methods'] ?? []),
            $routes['/s2j/v1/similarity']
        ));
        $this->assertContains('POST', $methods);
    }

    public function testEmbeddingRouteRegisteredWithNamespaceAndPost(): void
    {
        $routes = rest_get_server()->get_routes();
        $this->assertArrayHasKey('/s2j/v1/embedding', $routes);
        $methods = array_merge(...array_map(
            static fn (array $h): array => array_keys($h['methods'] ?? []),
            $routes['/s2j/v1/embedding']
        ));
        $this->assertContains('POST', $methods);
    }

    public function testPublicWordPressEndpointUsesWpJsonPrefix(): void
    {
        // WordPress external REST endpoint convention: /wp-json/<namespace>/<route>
        // docs/interfaces/rest_api_spec.md § OpenAPI パスと WordPress エンドポイント
        $url = rest_url('s2j/v1/similarity');
        $this->assertIsString($url);
        // In test/runtime environments (e.g. WorDBless) pretty permalinks may be disabled,
        // and WordPress falls back to the "rest_route" query parameter form.
        $this->assertTrue(
            str_contains($url, '/wp-json/s2j/v1/similarity')
                || str_contains($url, 'rest_route=/s2j/v1/similarity'),
            'Expected WordPress REST URL to include either "/wp-json/..." or "rest_route=/...". Got: ' . $url
        );
    }

    public function testSimilaritySuccessViaRestDispatch(): void
    {
        $request = $this->similarityJsonRequest([
            'textA' => 'hello',
            'textB' => 'world',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('similarityScore', $data['data']);
        $this->assertIsFloat($data['data']['similarityScore']);
        OpenApiResponseContractValidator::assertSimilaritySuccessBody($this, $data);
    }

    public function testSimilarityValidationErrorReturns400(): void
    {
        $request = $this->similarityJsonRequest([
            'textA' => 'hello',
            'textB' => '   ',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('validation_error', $data['error']['type'] ?? null);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testAuthenticationFailureReturns401(): void
    {
        $request = new WP_REST_Request('POST', '/s2j/v1/similarity');
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode([
            'textA' => 'a',
            'textB' => 'b',
        ]));

        $response = rest_do_request($request);

        $this->assertSame(401, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('auth_error', $data['code'] ?? null);
    }

    public function testAuthorizationDeniedAtPermissionCallbackReturns403(): void
    {
        self::$permissionGate->allowAuthenticated = false;

        $response = rest_do_request($this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]));

        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('permission_error', $data['code'] ?? null);
    }

    public function testAuthorizationErrorFromDomainMapsTo403(): void
    {
        self::$strategy->throwOnEmbed = new AuthorizationError('Insufficient scope');

        $response = rest_do_request($this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]));

        $this->assertSame(403, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('permission_error', $data['error']['type'] ?? null);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testRateLimitDomainErrorMapsTo429(): void
    {
        self::$strategy->throwOnEmbed = new RateLimitError('Slow down');

        $request = $this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(429, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('rate_limit', $data['error']['type'] ?? null);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testRateLimitIncludesRetryAfterHeaderWhenProvidedInDetails(): void
    {
        self::$strategy->throwOnEmbed = new RateLimitError('Slow down', ['retry_after' => 120]);

        $response = rest_do_request($this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]));

        $this->assertSame(429, $response->get_status());
        $headers = $response->get_headers();
        $retryAfter = $headers['Retry-After'] ?? $headers['retry-after'] ?? null;
        $this->assertNotNull($retryAfter);
        $this->assertSame('120', is_array($retryAfter) ? ($retryAfter[0] ?? '') : $retryAfter);
        $data = $response->get_data();
        $this->assertIsArray($data);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testTimeoutDomainErrorMapsTo408(): void
    {
        self::$strategy->throwOnEmbed = new TimeoutError('Upstream stalled');

        $request = $this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(408, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('timeout', $data['error']['type'] ?? null);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testProviderDomainErrorMapsTo503(): void
    {
        self::$strategy->throwOnEmbed = new ProviderError('Upstream unavailable');

        $request = $this->similarityJsonRequest([
            'textA' => 'a',
            'textB' => 'b',
        ]);

        $response = rest_do_request($request);

        $this->assertSame(503, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame('provider_error', $data['error']['type'] ?? null);
        OpenApiResponseContractValidator::assertErrorResponseBody($this, $data);
    }

    public function testEmbeddingSuccessViaRestDispatch(): void
    {
        self::$strategy->vector = [0.25, 0.75];

        $request = new WP_REST_Request('POST', '/s2j/v1/embedding');
        $request->set_header('Authorization', 'Bearer ' . self::INTEGRATION_TOKEN);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode(['text' => 'hello']));

        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertSame([0.25, 0.75], $data['data']['vector']);
        $this->assertSame(2, $data['data']['dimension']);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function similarityJsonRequest(array $body): WP_REST_Request
    {
        $request = new WP_REST_Request('POST', '/s2j/v1/similarity');
        $request->set_header('Authorization', 'Bearer ' . self::INTEGRATION_TOKEN);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($body));

        return $request;
    }
}

final class ControllableEmbeddingStrategy implements EmbeddingStrategyInterface
{
    public ?\Throwable $throwOnEmbed = null;

    /** @var float[] */
    public array $vector = [1.0, 0.0];

    public function embed(string $text, ?string $model = null): array
    {
        if ($this->throwOnEmbed !== null) {
            $e = $this->throwOnEmbed;
            throw $e;
        }

        return $this->vector;
    }
}

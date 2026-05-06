<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Adapters\Http\WordPress\Auth\BearerTokenAuth;
use S2J\Similarity\Adapters\Http\WordPress\Controllers\EmbeddingController;
use S2J\Similarity\Adapters\Http\WordPress\Controllers\SimilarityController;
use S2J\Similarity\Adapters\Http\WordPress\Routes;
use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Application\SimilarityService as AppSimilarityService;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;

final class WordPressAdapterTest extends TestCase
{
    public function testRoutesRegisterTwoPostEndpoints(): void
    {
        $strategy = new class implements EmbeddingStrategyInterface {
            public function embed(string $text, ?string $model = null): array
            {
                return [1.0, 0.0];
            }
        };

        $auth = new BearerTokenAuth(expectedToken: null);
        $sim = new SimilarityController(
            new AppSimilarityService($strategy),
            $auth
        );
        $emb = new EmbeddingController(
            new EmbeddingService(strategy: $strategy, provider: 'openai', defaultModel: 'text-embedding-3-small'),
            $auth
        );

        $calls = [];
        $register = function (string $ns, string $route, array $args) use (&$calls): void {
            $calls[] = [$ns, $route, $args];
        };

        Routes::register($sim, $emb, $register);

        $this->assertCount(2, $calls);
        $this->assertSame(Routes::DEFAULT_NAMESPACE, $calls[0][0]);
        $this->assertSame('/similarity', $calls[0][1]);
        $this->assertSame('POST', $calls[0][2]['methods']);
        $this->assertSame(Routes::DEFAULT_NAMESPACE, $calls[1][0]);
        $this->assertSame('/embedding', $calls[1][1]);
        $this->assertSame('POST', $calls[1][2]['methods']);
    }

    public function testSimilarityControllerReturnsErrorResponseOnValidationError(): void
    {
        $strategy = new class implements EmbeddingStrategyInterface {
            public function embed(string $text, ?string $model = null): array
            {
                return [1.0, 0.0];
            }
        };

        $auth = new BearerTokenAuth(expectedToken: null);
        $controller = new SimilarityController(new AppSimilarityService($strategy), $auth);

        $req = new class {
            public function get_json_params(): array
            {
                return ['textA' => 'a', 'textB' => '   '];
            }
            public function get_header(string $name): ?string
            {
                return null;
            }
        };

        $resp = $controller->handle($req);
        $this->assertIsArray($resp);
        $this->assertSame(400, $resp['status']);
        $this->assertSame('validation_error', $resp['body']['error']['type']);
    }
}

<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Contracts\BatchEmbeddingStrategyInterface;

final class EmbeddingServiceTest extends TestCase
{
    public function testEmbedWrapsVectorWithMetadata(): void
    {
        $strategy = new class implements EmbeddingStrategyInterface {
            public function embed(string $text, ?string $model = null): array
            {
                return [0.6, 0.8];
            }
        };

        $svc = new EmbeddingService(
            strategy: $strategy,
            provider: 'openai',
            defaultModel: 'm-default'
        );

        $e1 = $svc->embed("x");
        $this->assertSame('openai', $e1->provider);
        $this->assertSame('m-default', $e1->model);
        $this->assertSame(2, $e1->dimension);

        $e2 = $svc->embed("x", "m2");
        $this->assertSame('m2', $e2->model);
    }

    public function testEmbedBatchFallsBackWhenStrategyNotBatchCapable(): void
    {
        $calls = 0;
        $strategy = new class ($calls) implements EmbeddingStrategyInterface {
            public function __construct(private int &$calls)
            {
            }
            public function embed(string $text, ?string $model = null): array
            {
                $this->calls++;
                return [0.6, 0.8];
            }
        };

        $svc = new EmbeddingService($strategy, 'openai', 'm');
        $embs = $svc->embedBatch(['a', 'b']);

        $this->assertCount(2, $embs);
        $this->assertSame(2, $calls);
    }

    public function testEmbedBatchUsesBatchStrategyWhenAvailable(): void
    {
        $calls = 0;
        $strategy = new class ($calls) implements BatchEmbeddingStrategyInterface {
            public function __construct(private int &$calls)
            {
            }
            public function embed(string $text, ?string $model = null): array
            {
                $this->calls++;
                return [0.6, 0.8];
            }
            public function embedBatch(array $texts, ?string $model = null): array
            {
                $this->calls++;
                return array_map(static fn() => [0.6, 0.8], $texts);
            }
        };

        $svc = new EmbeddingService($strategy, 'openai', 'm');
        $embs = $svc->embedBatch(['a', 'b']);

        $this->assertCount(2, $embs);
        // embedBatch once
        $this->assertSame(1, $calls);
    }
}

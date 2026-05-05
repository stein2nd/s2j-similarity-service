<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Infrastructure\Cache\InMemoryCache;
use S2J\Similarity\Infrastructure\Embedding\CachedEmbeddingStrategy;

final class CachedEmbeddingStrategyTest extends TestCase
{
    public function testCachesByTextModelProvider(): void
    {
        $calls = 0;
        $inner = new class($calls) implements EmbeddingStrategyInterface {
            public function __construct(private int &$calls) {}
            public function embed(string $text, ?string $model = null): array
            {
                $this->calls++;
                return [1.0, 0.0];
            }
        };

        $cache = new InMemoryCache();
        $cached = new CachedEmbeddingStrategy(
            cache: $cache,
            inner: $inner,
            provider: 'openai'
        );

        $v1 = $cached->embed(" Hello ", "m1");
        $v2 = $cached->embed("hello", "m1"); // normalized text => hit
        $v3 = $cached->embed("hello", "m2"); // different model => miss

        $this->assertSame([1.0, 0.0], $v1);
        $this->assertSame([1.0, 0.0], $v2);
        $this->assertSame([1.0, 0.0], $v3);
        $this->assertSame(2, $calls);
    }

    public function testBatchUsesCacheAndPreservesOrder(): void
    {
        $calls = 0;
        $inner = new class($calls) implements EmbeddingStrategyInterface {
            public function __construct(private int &$calls) {}
            public function embed(string $text, ?string $model = null): array
            {
                $this->calls++;
                return match ($text) {
                    'a' => [1.0, 0.0],
                    'b' => [0.0, 1.0],
                    default => [0.0, 0.0],
                };
            }
        };

        $cache = new InMemoryCache();
        $cached = new CachedEmbeddingStrategy(
            cache: $cache,
            inner: $inner,
            provider: 'openai'
        );

        // Warm a single entry.
        $cached->embed('a', 'm1');
        $this->assertSame(1, $calls);

        $vectors = $cached->embedBatch(['a', 'b', 'a'], 'm1');
        $this->assertSame([[1.0, 0.0], [0.0, 1.0], [1.0, 0.0]], $vectors);
        // Only 'b' should be a miss.
        $this->assertSame(2, $calls);
    }
}

<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;

final class NewSimilarityServiceTest extends TestCase
{
    public function testSimilarityUsesStrategyAndReturnsFloat(): void
    {
        $strategy = new class implements EmbeddingStrategyInterface {
            public function embed(string $text, ?string $model = null): array
            {
                // Return a deterministic vector.
                return [1.0, 2.0, 3.0];
            }
        };

        $svc = new SimilarityService($strategy);
        $score = $svc->similarity('a', 'b');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
        $this->assertEqualsWithDelta(1.0, $score, 1e-9); // same vectors => cos=1 => score=1
    }

    public function testBatchApisPreserveOrder(): void
    {
        $strategy = new class implements EmbeddingStrategyInterface {
            public function embed(string $text, ?string $model = null): array
            {
                // Map text to simple 2D vectors.
                return match ($text) {
                    'q' => [1.0, 0.0],
                    'a' => [1.0, 0.0],
                    'b' => [0.0, 1.0],
                    default => [0.0, 0.0],
                };
            }
        };

        $svc = new SimilarityService($strategy);

        $scores = $svc->similarityOneToMany('q', ['b', 'a']);
        $this->assertCount(2, $scores);
        // b first => cos=0 => score=0.5; a second => cos=1 => score=1
        $this->assertEqualsWithDelta(0.5, $scores[0], 1e-9);
        $this->assertEqualsWithDelta(1.0, $scores[1], 1e-9);

        $matrix = $svc->similarityMatrix(['a', 'b']);
        $this->assertCount(2, $matrix);
        $this->assertCount(2, $matrix[0]);
        $this->assertEqualsWithDelta(1.0, $matrix[0][0], 1e-9);
        $this->assertEqualsWithDelta(0.5, $matrix[0][1], 1e-9);
    }
}

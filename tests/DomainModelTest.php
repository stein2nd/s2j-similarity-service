<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Contracts\Errors\ValidationError;
use S2J\Similarity\Domain\Model\Embedding;

final class DomainModelTest extends TestCase
{
    public function testEmbeddingFromVectorSetsDimension(): void
    {
        $e = Embedding::fromVector([0.6, 0.8], "m", "openai");
        $this->assertSame(2, $e->dimension);
        $this->assertSame("m", $e->model);
        $this->assertSame("openai", $e->provider);
        $this->assertSame([0.6, 0.8], $e->vector);
    }

    public function testEmbeddingRejectsDimensionMismatch(): void
    {
        $this->expectException(ValidationError::class);
        new Embedding(vector: [1.0, 2.0], dimension: 3, model: "m", provider: "p");
    }
}

<?php

use PHPUnit\Framework\TestCase;
use S2J\Similarity\Contracts\Errors\CalculationError;
use S2J\Similarity\Contracts\Errors\InvalidArgumentError;
use S2J\Similarity\Contracts\Errors\ValidationError;
use S2J\Similarity\Core\VectorMath;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

final class DomainErrorTest extends TestCase
{
    public function testVectorMathThrowsTypedErrors(): void
    {
        $this->expectException(InvalidArgumentError::class);
        VectorMath::cosineSimilarity([1.0, 2.0], [1.0]);
    }

    public function testVectorMathNormalizeZeroVectorThrowsCalculationError(): void
    {
        $this->expectException(CalculationError::class);
        VectorMath::l2Normalize([0.0, 0.0]);
    }

    public function testOpenAiEmbeddingStrategyRejectsEmptyTextAsValidationError(): void
    {
        $strategy = new OpenAIEmbeddingStrategy(apiKey: "dummy");
        $this->expectException(ValidationError::class);
        $strategy->embed("   ");
    }
}

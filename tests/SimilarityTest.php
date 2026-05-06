<?php
use PHPUnit\Framework\TestCase;
use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Infrastructure\Embedding\OpenAIEmbeddingStrategy;

final class SimilarityTest extends TestCase
{
    public function testSimilarity()
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            $this->markTestSkipped('OPENAI_API_KEY environment variable is not set');
        }

        $strategy = new OpenAIEmbeddingStrategy(
            apiKey: $apiKey,
            defaultModel: 'text-embedding-3-small'
        );
        $service = new SimilarityService($strategy);

        $score = $service->similarity(
            '猫はかわいい',
            '犬はかわいい',
            'text-embedding-3-small'
        );

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }
}

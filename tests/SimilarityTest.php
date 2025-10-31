<?php
use PHPUnit\Framework\TestCase;
use S2J\SimilarityService\SimilarityService;
use S2J\SimilarityService\OpenAIEmbeddingStrategy;

final class SimilarityTest extends TestCase
{
    public function testCompare()
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            $this->markTestSkipped('OPENAI_API_KEY environment variable is not set');
        }

        $strategy = new OpenAIEmbeddingStrategy();
        $service = new SimilarityService($strategy);

        $result = $service->compare(
            $apiKey,
            'text-embedding-3-small',
            'ja',
            'ja_JP',
            '猫はかわいい',
            '犬はかわいい'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('similarity', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('language', $result);
        $this->assertIsFloat($result['similarity']);
        $this->assertGreaterThanOrEqual(0.0, $result['similarity']);
        $this->assertLessThanOrEqual(1.0, $result['similarity']);
        $this->assertEquals('text-embedding-3-small', $result['model']);
        $this->assertEquals('ja', $result['language']);
    }
}

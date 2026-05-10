<?php

namespace S2J\Similarity\Infrastructure\Embedding;

use S2J\Similarity\Contracts\BatchEmbeddingStrategyInterface;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Infrastructure\Cache\CacheInterface;

final class CachedEmbeddingStrategy implements BatchEmbeddingStrategyInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly EmbeddingStrategyInterface $inner,
        private readonly string $provider,
        private readonly int $ttlSeconds = 86400
    ) {
    }

    public function embed(string $text, ?string $model = null): array
    {
        $key = self::cacheKey(
            text: self::normalizeText($text),
            model: $model,
            provider: $this->provider
        );

        $cached = $this->cache->get($key);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $vector = $this->inner->embed($text, $model);
        $this->cache->set($key, $vector, $this->ttlSeconds);
        return $vector;
    }

    public function embedBatch(array $texts, ?string $model = null): array
    {
        $results = [];
        $misses = [];
        $missIndexes = [];

        foreach ($texts as $i => $text) {
            $key = self::cacheKey(
                text: self::normalizeText((string)$text),
                model: $model,
                provider: $this->provider
            );

            $cached = $this->cache->get($key);
            if (is_array($cached) && $cached !== []) {
                $results[$i] = $cached;
                continue;
            }

            $missIndexes[] = $i;
            $misses[] = (string)$text;
        }

        if ($misses !== []) {
            if ($this->inner instanceof BatchEmbeddingStrategyInterface) {
                $missVectors = $this->inner->embedBatch($misses, $model);
            } else {
                $missVectors = [];
                foreach ($misses as $t) {
                    $missVectors[] = $this->inner->embed($t, $model);
                }
            }

            foreach ($missVectors as $j => $vec) {
                $i = $missIndexes[$j];
                $text = (string)$texts[$i];
                $key = self::cacheKey(
                    text: self::normalizeText($text),
                    model: $model,
                    provider: $this->provider
                );

                $this->cache->set($key, $vec, $this->ttlSeconds);
                $results[$i] = $vec;
            }
        }

        ksort($results);
        return array_values($results);
    }

    private static function normalizeText(string $text): string
    {
        $t = trim($text);
        if (function_exists('mb_strtolower')) {
            $t = mb_strtolower($t, 'UTF-8');
        } else {
            $t = strtolower($t);
        }
        return $t;
    }

    private static function cacheKey(
        string $text,
        ?string $model,
        string $provider
    ): string {
        $payload = json_encode(
            [
                'text' => $text,
                'model' => $model ?? '__default__',
                'provider' => $provider,
                'normalized' => true,
            ],
            JSON_UNESCAPED_UNICODE
        );

        return hash('sha256', $payload === false ? '' : $payload);
    }
}

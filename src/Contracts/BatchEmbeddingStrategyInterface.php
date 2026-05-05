<?php

namespace S2J\Similarity\Contracts;

interface BatchEmbeddingStrategyInterface extends EmbeddingStrategyInterface
{
    /**
     * @param string[] $texts
     * @return array<int, float[]> Normalized vectors; order must match input.
     */
    public function embedBatch(
        array $texts,
        ?string $model = null
    ): array;
}

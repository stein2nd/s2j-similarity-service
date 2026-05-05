<?php

namespace S2J\Similarity\Contracts;

interface EmbeddingStrategyInterface
{
    /**
     * @return float[] Normalized embedding vector (L2).
     */
    public function embed(
        string $text,
        ?string $model = null
    ): array;
}

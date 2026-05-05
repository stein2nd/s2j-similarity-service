<?php

namespace S2J\Similarity\Domain\Model;

use S2J\Similarity\Contracts\Errors\ValidationError;

final class SimilarityRequest
{
    /**
     * @param float[] $vectorA
     * @param float[] $vectorB
     */
    public function __construct(
        public readonly array $vectorA,
        public readonly array $vectorB,
        public readonly ?string $model = null
    ) {
        if ($this->vectorA === [] || $this->vectorB === []) {
            throw new ValidationError("vectorA/vectorB must not be empty");
        }
    }
}

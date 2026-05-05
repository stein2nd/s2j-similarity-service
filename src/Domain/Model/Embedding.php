<?php

namespace S2J\Similarity\Domain\Model;

use S2J\Similarity\Contracts\Errors\ValidationError;

final class Embedding
{
    /**
     * @param float[] $vector Normalized (unit) vector.
     */
    public function __construct(
        public readonly array $vector,
        public readonly int $dimension,
        public readonly string $model,
        public readonly string $provider
    ) {
        if ($this->dimension < 1) {
            throw new ValidationError("dimension must be >= 1");
        }

        if ($this->vector === []) {
            throw new ValidationError("vector must not be empty");
        }

        if (count($this->vector) !== $this->dimension) {
            throw new ValidationError("vector.length must equal dimension", [
                'vectorLength' => count($this->vector),
                'dimension' => $this->dimension,
            ]);
        }

        foreach ($this->vector as $x) {
            $fx = (float)$x;
            if (!is_finite($fx)) {
                throw new ValidationError("vector must not contain NaN/Infinity");
            }
        }

        if (trim($this->model) === '') {
            throw new ValidationError("model must not be empty");
        }

        if (trim($this->provider) === '') {
            throw new ValidationError("provider must not be empty");
        }
    }

    /**
     * @param float[] $vector Normalized (unit) vector.
     */
    public static function fromVector(
        array $vector,
        string $model,
        string $provider
    ): self {
        return new self(
            vector: array_map(static fn($x) => (float)$x, $vector),
            dimension: count($vector),
            model: $model,
            provider: $provider
        );
    }
}

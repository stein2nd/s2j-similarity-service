<?php

namespace S2J\Similarity\Domain\Model;

final class SimilarityResponse
{
    public function __construct(
        public readonly float $similarityScore
    ) {}
}

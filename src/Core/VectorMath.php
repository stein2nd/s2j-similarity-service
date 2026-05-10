<?php

namespace S2J\Similarity\Core;

use S2J\Similarity\Contracts\Errors\CalculationError;
use S2J\Similarity\Contracts\Errors\InvalidArgumentError;
use S2J\Similarity\Contracts\Errors\ValidationError;

final class VectorMath
{
    /**
     * @param float[] $v
     * @return float[]
     */
    public static function l2Normalize(array $v): array
    {
        if ($v === []) {
            throw new ValidationError("Vector must not be empty.");
        }

        $sumSq = 0.0;
        foreach ($v as $x) {
            if (!is_float($x) && !is_int($x)) {
                throw new ValidationError("Vector must contain only numbers.");
            }

            $fx = (float)$x;
            if (!is_finite($fx)) {
                throw new ValidationError("Vector must not contain NaN/Infinity.");
            }

            $sumSq += $fx * $fx;
        }

        if ($sumSq === 0.0) {
            throw new CalculationError("Cannot normalize a zero vector.");
        }

        $norm = sqrt($sumSq);
        return array_map(
            static fn ($x) => ((float)$x) / $norm,
            $v
        );
    }

    /**
     * Similarity score in range [0.0, 1.0].
     *
     * @param float[] $a
     * @param float[] $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            throw new ValidationError("Vectors must not be empty.");
        }

        if (count($a) !== count($b)) {
            throw new InvalidArgumentError("Vector dimensions do not match.");
        }

        $dot = 0.0;
        $sumSqA = 0.0;
        $sumSqB = 0.0;

        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $ai = (float)$a[$i];
            $bi = (float)$b[$i];

            if (!is_finite($ai) || !is_finite($bi)) {
                throw new ValidationError("Vectors must not contain NaN/Infinity.");
            }

            $dot += $ai * $bi;
            $sumSqA += $ai * $ai;
            $sumSqB += $bi * $bi;
        }

        if ($sumSqA === 0.0 || $sumSqB === 0.0) {
            return 0.0;
        }

        $cos = $dot / (sqrt($sumSqA) * sqrt($sumSqB)); // [-1, 1] (ideally)
        $score = ($cos + 1.0) / 2.0; // [0, 1]

        if ($score < 0.0) {
            return 0.0;
        }
        if ($score > 1.0) {
            return 1.0;
        }
        return $score;
    }
}

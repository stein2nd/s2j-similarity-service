<?php

namespace S2J\Similarity\Contracts\Errors;

final class CalculationError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Calculation failed", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("CalculationError", $message, $details, $previous);
    }
}

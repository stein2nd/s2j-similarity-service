<?php

namespace S2J\Similarity\Contracts\Errors;

final class ValidationError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Invalid input", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("ValidationError", $message, $details, $previous);
    }
}

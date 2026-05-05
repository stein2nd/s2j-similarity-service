<?php

namespace S2J\Similarity\Contracts\Errors;

final class TimeoutError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Timeout", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("TimeoutError", $message, $details, $previous);
    }
}

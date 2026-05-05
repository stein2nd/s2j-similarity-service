<?php

namespace S2J\Similarity\Contracts\Errors;

final class RateLimitError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Rate limited", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("RateLimitError", $message, $details, $previous);
    }
}

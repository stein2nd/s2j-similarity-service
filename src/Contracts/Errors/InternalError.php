<?php

namespace S2J\Similarity\Contracts\Errors;

final class InternalError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Internal error", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("internal_error", $message, $details, $previous);
    }
}

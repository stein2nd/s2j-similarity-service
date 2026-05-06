<?php

namespace S2J\Similarity\Contracts\Errors;

final class NotFoundError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Not found", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("not_found", $message, $details, $previous);
    }
}

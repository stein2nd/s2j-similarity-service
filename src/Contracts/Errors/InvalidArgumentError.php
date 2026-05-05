<?php

namespace S2J\Similarity\Contracts\Errors;

final class InvalidArgumentError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Invalid argument", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("InvalidArgumentError", $message, $details, $previous);
    }
}

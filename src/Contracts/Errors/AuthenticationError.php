<?php

namespace S2J\Similarity\Contracts\Errors;

final class AuthenticationError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Unauthorized", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("auth_error", $message, $details, $previous);
    }
}

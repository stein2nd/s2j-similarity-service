<?php

namespace S2J\Similarity\Contracts\Errors;

final class AuthorizationError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Forbidden", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("permission_error", $message, $details, $previous);
    }
}

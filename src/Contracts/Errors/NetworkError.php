<?php

namespace S2J\Similarity\Contracts\Errors;

final class NetworkError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Network error", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("NetworkError", $message, $details, $previous);
    }
}

<?php

namespace S2J\Similarity\Contracts\Errors;

final class ProviderError extends DomainError
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(string $message = "Provider error", array $details = [], ?\Throwable $previous = null)
    {
        parent::__construct("ProviderError", $message, $details, $previous);
    }
}

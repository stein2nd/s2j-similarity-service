<?php

namespace S2J\Similarity\Contracts\Errors;

abstract class DomainError extends \RuntimeException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly string $type,
        string $message,
        public readonly array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

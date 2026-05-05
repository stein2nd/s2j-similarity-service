<?php

namespace S2J\Similarity\Infrastructure\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void;
}

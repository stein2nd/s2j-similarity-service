<?php

namespace S2J\Similarity\Infrastructure\Cache;

final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, array{expiresAt: int|null, value: mixed}>
     */
    private array $store = [];

    public function get(string $key): mixed
    {
        $item = $this->store[$key] ?? null;
        if ($item === null) {
            return null;
        }

        $expiresAt = $item['expiresAt'];
        if ($expiresAt !== null && $expiresAt < time()) {
            unset($this->store[$key]);
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $expiresAt = null;
        if ($ttlSeconds !== null) {
            $expiresAt = time() + $ttlSeconds;
        }

        $this->store[$key] = [
            'expiresAt' => $expiresAt,
            'value' => $value,
        ];
    }
}

<?php

namespace S2J\Similarity\Infrastructure\Embedding;

use S2J\Similarity\Contracts\BatchEmbeddingStrategyInterface;
use S2J\Similarity\Contracts\Errors\NetworkError;
use S2J\Similarity\Contracts\Errors\ProviderError;
use S2J\Similarity\Contracts\Errors\RateLimitError;
use S2J\Similarity\Contracts\Errors\TimeoutError;
use S2J\Similarity\Contracts\Errors\ValidationError;
use S2J\Similarity\Core\VectorMath;

final class OpenAIEmbeddingStrategy implements BatchEmbeddingStrategyInterface
{
    private const DEFAULT_ENDPOINT = 'https://api.openai.com/v1/embeddings';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $defaultModel = 'text-embedding-3-small',
        private readonly string $endpoint = self::DEFAULT_ENDPOINT,
        private readonly int $timeoutSeconds = 30
    ) {}

    public function embed(string $text, ?string $model = null): array
    {
        $vectors = $this->embedBatch([$text], $model);
        return $vectors[0];
    }

    public function embedBatch(array $texts, ?string $model = null): array
    {
        $trimmed = [];
        foreach ($texts as $i => $t) {
            $s = trim((string)$t);
            if ($s === '') {
                throw new ValidationError("text must not be empty.", ['index' => $i]);
            }
            $trimmed[$i] = $s;
        }

        $payload = json_encode(
            [
                'model' => $model ?? $this->defaultModel,
                'input' => array_values($trimmed),
            ],
            JSON_UNESCAPED_UNICODE
        );

        if ($payload === false) {
            throw new ProviderError('Failed to encode request payload.');
        }

        $ch = curl_init($this->endpoint);
        if ($ch === false) {
            throw new ProviderError('Failed to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->apiKey}",
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $errno = curl_errno($ch);
            $err = curl_error($ch);
            curl_close($ch);

            // CURLE_OPERATION_TIMEDOUT = 28
            if ($errno === 28) {
                throw new TimeoutError('OpenAI API request timed out.', ['provider' => 'openai'], null);
            }

            throw new NetworkError('OpenAI API request failed: ' . $err, ['provider' => 'openai'], null);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new ProviderError('OpenAI API returned invalid JSON.', ['provider' => 'openai']);
        }

        if ($status !== 200) {
            $errorMessage = $data['error']['message'] ?? 'Unknown error';
            if ($status === 429) {
                throw new RateLimitError("OpenAI API rate limited: {$errorMessage}", ['provider' => 'openai', 'status' => $status]);
            }

            throw new ProviderError("OpenAI API returned error ({$status}): {$errorMessage}", ['provider' => 'openai', 'status' => $status]);
        }

        $items = $data['data'] ?? null;
        if (!is_array($items) || $items === []) {
            throw new ProviderError('OpenAI API response missing embedding vectors.', ['provider' => 'openai']);
        }

        $vectors = [];
        foreach ($items as $idx => $row) {
            $vec = $row['embedding'] ?? null;
            if (!is_array($vec) || $vec === []) {
                throw new ProviderError('OpenAI API response missing embedding vector.', ['provider' => 'openai', 'index' => $idx]);
            }
            $vectors[$idx] = VectorMath::l2Normalize($vec);
        }

        ksort($vectors);
        return array_values($vectors);
    }
}

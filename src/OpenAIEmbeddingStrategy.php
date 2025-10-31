<?php
/**
 * OpenAIEmbeddingStrategy
 *
 * OpenAI Embeddings API を利用して埋め込みベクトルを生成する Strategy。
 *
 * @package S2J\SimilarityService
 * @license GPL-2.0-or-later
 */

namespace S2J\SimilarityService;

use Exception;

class OpenAIEmbeddingStrategy implements EmbeddingStrategyInterface
{
    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/embeddings';

    /**
     * 与えられたテキストから埋め込みベクトルを生成。
     *
     * @param string $apiKey OpenAI API Key
     * @param string $model モデル名 (例: text-embedding-3-small)
     * @param string $text テキスト本文
     * @param string $language 言語コード (例: ja、en、fr)
     * @param string|null $locale ロケール (例: ja_JP、en_US、fr_FR)
     * @return array ベクトル (float の配列)
     */
    public function getEmbedding(string $apiKey, string $model, string $text, string $language, ?string $locale = null): array
    {
        $payload = json_encode([
            'model' => $model,
            'input' => $text,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::OPENAI_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$apiKey}"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('OpenAI API request failed: ' . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($status !== 200) {
            $errorMessage = $data['error']['message'] ?? 'Unknown error';
            throw new Exception("OpenAI API returned error ({$status}): {$errorMessage}");
        }

        return $data['data'][0]['embedding'] ?? [];
    }
}

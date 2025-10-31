<?php
/**
 * EmbeddingStrategyInterface
 *
 * 定義: 任意の Embedding モデルを抽象化する Strategy パターン・インターフェイス。
 *
 * @package S2J\SimilarityService
 * @license GPL-2.0-or-later
 */

namespace S2J\SimilarityService;

interface EmbeddingStrategyInterface
{
    /**
     * 与えられたテキストから埋め込みベクトルを生成。
     *
     * @param string $apiKey API Key
     * @param string $model モデル名 (例: text-embedding-3-small)
     * @param string $text テキスト本文
     * @param string $language 言語コード (例: ja、en、fr)
     * @param string|null $locale ロケール (例: ja_JP、en_US、fr_FR)
     * @return array ベクトル (float の配列)
     * 
     * @throws \Exception API 通信エラーや形式不正など
     */
     public function getEmbedding(string $apiKey, string $model, string $text, string $language, ?string $locale = null): array;
}

<!--
目的：「類似度算出ロジック」の明文化
-->

# 類似度算出の仕様

## 処理の流れ

1. 基準テキスト本文と検証テキスト本文を、それぞれ Embedding API でベクトル化する。
2. 得られた2つのベクトルについて、**コサイン類似度** を計算する。
3. スコアを0.0〜1.0の範囲で返す (小数桁は仕様上6桁に丸める)。

## コサイン類似度の定義

2つのベクトル (\boldsymbol{a})、(\boldsymbol{b}) に対し、コサイン類似度は次で定義する。

```
[
\mathrm{similarity}(\boldsymbol{a}, \boldsymbol{b})
= \frac{\boldsymbol{a} \cdot \boldsymbol{b}}{\|\boldsymbol{a}\| \cdot \|\boldsymbol{b}\|}
= \frac{\sum_i a_i b_i}{\sqrt{\sum_i a_i^2} \cdot \sqrt{\sum_i b_i^2}}
]
```

* **範囲**: ( `0.0 \leq \mathrm{similarity} \leq 1.0` ) (OpenAI の埋め込みは正規化を前提とした利用を想定)
* **解釈**: 1.0に近いほど意味的に類似、0に近いほど無関係。

## ゼロベクトル・長さ不一致の扱い

* いずれかのベクトルのノルムが0の場合、類似度は **0.0** とする。
* 2つのベクトルの次元が異なる場合、**短いうの長さ** までのみを用いて内積・ノルムを計算する (`VectorMath::cosineSimilarity` の実装に準拠)。

## 戻り値での丸め

算出した類似度は、**小数第6位で四捨五入** した値を返す。

```php
'similarity' => round($similarity, 6)
```

## 対応する実装

* **計算**: `S2J\SimilarityService\VectorMath::cosineSimilarity(array $vecA, array $vecB): float`
* **利用箇所**: `S2J\SimilarityService\SimilarityService::compare()` 内で、2つの Embedding ベクトルに対して呼び出される。

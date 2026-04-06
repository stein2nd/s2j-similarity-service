<!--
目的：「類似度算出の仕様」の明文化
-->

# S2J Similarity Service - 類似度算出の仕様

本ドキュメントは、「類似度算出ロジック」の仕様を定義し、実装の指針を提供する。

## 設計意図、方針、非対象

### 設計意図 (ゴール)

2つのテキスト間の意味的な近さを、**一貫した数値 (スコア) として定量化** します。

### 設計方針 (規約)

* Embedding ベースの類似度計算を採用します。
* 「cosine similarity」を基本アルゴリズムとします。
* 出力スコアは、**0.0〜1.0に正規化** します。
* 入出力は、純粋関数として扱います (副作用なし)。

### 非対象 (Out of Scope)

* Embedding の生成 (外部API依存)
* テキスト前処理 (正規化・トークナイズ)
* 類似度に基づくランキング・検索最適化
* 学習・モデルチューニング

## 本仕様の責務

本仕様は、以下のみを扱います。

* 2つのベクトルから類似度スコアを算出します。
* スコアの正規化ルールを定義します。

* 正規化は、Core 層で必ず実施します。上位レイヤーは、未正規化値を扱ってはなりません。

## 用語、前提

* **[Embedding Vector](../contracts/data_dictionary.md)**
  * テキストを数値ベクトルに変換したものです。
  * 次元数は、(1536次元など) 固定です。
* **cosine similarity**
  * 2ベクトル間の角度に基づく類似度指標です。
  * 範囲は、-1.0〜1.0です。

## 入力

### 型

```plaintext id="input_type"
vectorA: float[]
vectorB: float[]
```

### 制約

* `vectorA.length === vectorB.length`
* 要素は、(NaN、Infinity を含まない) 有限な数値です。
* 次元数は、1以上です。

## 出力

### 型

```plaintext id="output_type"
similarityScore: float
```

### 範囲

```plaintext id="output_range"
0.0 <= similarityScore <= 1.0
```

### 意味

* 0.0: 全く類似しない
* 0.5: 中程度の類似
* 1.0: 完全一致

## アルゴリズム

### cosine similarity

\cos(\theta)=\frac{A\cdot B}{|A||B|}

### 処理手順

```plaintext id="algo_steps"
1. 内積を計算
   dot = Σ (A_i * B_i)

2. ノルムを計算
   normA = sqrt(Σ A_i^2)
   normB = sqrt(Σ B_i^2)

3. cosine similarity を算出
   cos = dot / (normA * normB)
```

## 正規化ルール

### 設計意図 (ゴール)

「cosine similarity」の範囲 (-1〜1) を、扱いやすいスコア (0〜1) に変換します。

### 正規化の責務

スコアの正規化は、Core 層で必ず実施します。
上位レイヤー (Application、Interfaces) は、未正規化の値を扱ってはなりません。

### 変換式

score = \frac{cos + 1}{2}

### 補足

* 負の値も、0〜1にマッピングされます。
* スコア比較が、直感的になります。

## エッジケース

### ゼロベクトル

```plaintext id="edge_zero"
normA == 0 または normB == 0
```

本ケースの対応は、`similarityScore = 0.0` とします。

### 次元の不一致

```plaintext id="edge_dim"
vectorA.length !== vectorB.length
```

本ケースの対応は、「`InvalidArgument` エラーを投げる」とします。

### 不正値 (NaN / Infinity)

本ケースの対応は、「エラーを投げる」とします。

### 極端に近い値 (浮動小数の誤差)

本ケースの対応は、「最終スコアを clamp」とします。

```plaintext id="clamp"
score = max(0.0, min(1.0, score))
```

## 参考実装 (擬似コード)

```plaintext id="pseudo"
function calculateSimilarity(vectorA, vectorB):
    if length mismatch:
        throw error

    dot = 0
    normA = 0
    normB = 0

    for i in range:
        dot += A[i] * B[i]
        normA += A[i]^2
        normB += B[i]^2

    normA = sqrt(normA)
    normB = sqrt(normB)

    if normA == 0 or normB == 0:
        return 0.0

    cos = dot / (normA * normB)

    score = (cos + 1) / 2

    return clamp(score, 0.0, 1.0)
```

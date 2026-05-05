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

* Embedding の生成 (外部 API 依存)
* テキスト前処理 (正規化・トークナイズ)
* 類似度にもとづくランキング・検索最適化
* 学習・モデルチューニング

## 本仕様の責務

本仕様は、以下のみを扱います。

* 2つのベクトルから類似度スコアを算出すること。
* スコアの正規化ルールを定義すること。

* 正規化は、Core 層で必ず実施すること。上位レイヤは、未正規化値を扱ってはなりません。

## 用語、前提

* **[Embedding Vector](../contracts/data_dictionary.md)**
  * テキストを数値ベクトルに変換したものです。
  * 次元数は、(1536次元など) 固定です。
* **cosine similarity**
  * 2ベクトル間の角度にもとづく類似度指標です。
  * 範囲は、-1.0〜1.0です。

## 数値精度

### 設計方針 (規約)

* 浮動小数点の誤差を許容します。
* 厳密一致は、保証しません。

### 推奨

* epsilon を用いた比較

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

* 0.0: まったく類似しない
* 0.5: 中程度の類似
* 1.0: 完全一致

## アルゴリズム

### cosine similarity

```
\cos(\theta)=\frac{A\cdot B}{|A||B|}
```

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

## 類似度計算の前提条件

### 設計意図 (ゴール)

類似度スコアの意味を一意に定義し、実装、API、SDK 間で解釈のズレを防ぎます。

### 設計方針 (規約)

* 類似度計算には、cosine similarity を使用します。
* 入力ベクトルは、正規化済み (L2正規化) とします。
* 出力スコアは、0〜1の範囲に正規化します。

### 責務

* 類似度計算アルゴリズムを定義すること。
* スコアの意味を保証すること。

### 非責務

* Embedding の取得
* ベクトル正規化の実装
* API レスポンス形式

### 数式

```plaintext id="cosine_formula"
cosine_similarity = (A · B) / (||A|| * ||B||)
```

正規化済みベクトルの場合は、下記になります。

```plaintext id="cosine_simplified"
cosine_similarity = A · B
```

### スコア正規化

cosine similarity の値域は [-1, 1] であるため、以下の式で0〜1に変換します。

```plaintext id="score_normalization"
score = (cosine_similarity + 1) / 2
```

### 入力前提

* vector は、float 配列とします。
* すべてのベクトルは、同一次元であること。
* ベクトルは、`unit vector (||v|| = 1)` であること。

### 出力仕様

| 項目 | 内容 |
|------|------|
| 範囲 | 0.0〜1.0 |
| 意味 | 1.0に近いほど意味的に近い |
| 単位 | 無次元 |

### エッジケース

* ベクトル長が異なる場合 → エラー
* null / 空配列 → エラー
* NaN を含む場合 → エラー

## 類似度計算のバッチ版

### 設計意図 (ゴール)

単一比較だけでなく、複数テキスト間の、効率的な類似度計算を可能にします。

### 責務

* バッチ計算ロジックを定義すること。
* 出力形式を保証すること。

### 非責務

* Embedding 取得
* 並列実行の制御
* 外部 API 制約

### 対応パターン

#### `1×N` (単一対複数)

```plaintext id="batch_1n"
query × candidates[]
```

#### `N×N` (全組み合わせ)

```plaintext id="batch_nn"
matrix[i][j] = similarity(v_i, v_j)
```

### 入力仕様

* 同一次元のベクトル配列。
* 正規化済みベクトル。

### 出力仕様

* `1×N` (単一対複数) → score 配列
* `N×N` (全組み合わせ) → 対称行列

### 計算量 O

* `1×N` (単一対複数) → `O = (N)`
* `N×N` (全組み合わせ) → `O = (N²)`

## 類似度計算の非同期境界

### 設計意図 (ゴール)

類似度計算を純粋関数として定義し、テスト容易性と予測可能性を確保します。

### 設計方針 (規約)

* 類似度計算は、同期処理とします。
* 外部 I/O に依存しません。
* Promise を返しません。

### 責務

* ベクトル演算を実装すること。
* スコアを算出すること。

### 非責務

* Embedding 取得
* 非同期処理
* リトライ、キャッシュ

### 特性

* deterministic (同一入力 → 同一出力)
* side-effect なし
* 高速計算

### インターフェース

```ts id="similarity_sync"
function cosineSimilarity(a: number[], b: number[]): number
```

## ベクトル次元の整合性

### 設計意図 (ゴール)

異なる次元の Embedding による誤計算を防止します。

### 設計方針 (規約)

* 異なる次元のベクトルは計算不可とします。
* 実行時に検証し、例外を投げます。

### 責務

* 入力整合性をチェックすること。

### 非責務

* 自動補完
* パディング処理

### 仕様

```ts
if (a.length !== b.length) {
  throw new DimensionMismatchError()
}
```

## 正規化ルール

### 設計意図 (ゴール)

「cosine similarity」の範囲 (-1〜1) を、扱いやすいスコア (0〜1) に変換します。

### 正規化の責務

スコアの正規化は、Core 層で必ず実施すること。上位レイヤ (Application、Interfaces) は、未正規化の値を扱ってはなりません。

### 変換式

```
score = \frac{cos + 1}{2}
```

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

## 類似度閾値 (参考値)

### 設計意図 (ゴール)

ユーザーがスコアを解釈しやすくします。

### 参考基準

| スコア | 意味 |
|-------|------|
| 0.9以上 | 非常に類似 |
| 0.7以上 | 類似 |
| 0.5未満 | 低類似 |

### 注意

閾値は、ユースケース依存であり、固定値ではありません。

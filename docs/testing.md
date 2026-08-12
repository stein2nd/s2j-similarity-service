<!-- 
 テスト戦略
 -->

# S2J Similarity Service - テスト戦略

## Embedding のテスト戦略

### 設計意図 (ゴール)

外部 API に依存しないテストを実現します。

### 設計方針 (規約)

* フェイク／スタブは、EmbeddingStrategyInterface を実装します。

### 例

```php
class FakeEmbeddingStrategy implements EmbeddingStrategyInterface
{
    public function embed(string $text, ?string $model = null): array
    {
        return [0.1, 0.2, 0.3];
    }
}
```

### 利点

* 外部 API 不要
* deterministic なテスト

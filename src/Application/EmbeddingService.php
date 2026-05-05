<?php

namespace S2J\Similarity\Application;

use S2J\Similarity\Contracts\BatchEmbeddingStrategyInterface;
use S2J\Similarity\Contracts\EmbeddingStrategyInterface;
use S2J\Similarity\Contracts\Errors\ValidationError;
use S2J\Similarity\Domain\Model\Embedding;

final class EmbeddingService
{
    public function __construct(
        private readonly EmbeddingStrategyInterface $strategy,
        private readonly string $provider,
        private readonly string $defaultModel
    ) {
        if (trim($this->provider) === '') {
            throw new ValidationError("provider must not be empty");
        }
        if (trim($this->defaultModel) === '') {
            throw new ValidationError("defaultModel must not be empty");
        }
    }

    public function embed(
        string $text,
        ?string $model = null
    ): Embedding {
        $usedModel = $model ?? $this->defaultModel;
        $vector = $this->strategy->embed($text, $usedModel);

        return Embedding::fromVector(
            vector: $vector,
            model: $usedModel,
            provider: $this->provider
        );
    }

    /**
     * @param string[] $texts
     * @return Embedding[] (same order as inputs)
     */
    public function embedBatch(
        array $texts,
        ?string $model = null
    ): array {
        $usedModel = $model ?? $this->defaultModel;

        $vectors = null;
        if ($this->strategy instanceof BatchEmbeddingStrategyInterface) {
            $vectors = $this->strategy->embedBatch($texts, $usedModel);
        }

        if ($vectors === null) {
            $vectors = [];
            foreach ($texts as $i => $text) {
                $vectors[$i] = $this->strategy->embed((string)$text, $usedModel);
            }
        }

        ksort($vectors);
        $vectors = array_values($vectors);

        $embeddings = [];
        foreach ($vectors as $v) {
            $embeddings[] = Embedding::fromVector(
                vector: $v,
                model: $usedModel,
                provider: $this->provider
            );
        }

        return $embeddings;
    }
}

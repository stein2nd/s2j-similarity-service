<?php

declare(strict_types=1);

namespace S2J\Similarity\Tests\Support;

use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Validates real {@see WP_REST_Response} bodies against JSON Schemas derived from {@see schema/openapi.yaml}.
 *
 * Aligns with docs/engineering/ci.md § OpenAPI レスポンス契約検証 (JSON Schema Validation).
 */
final class OpenApiResponseContractValidator
{
    /** @var array<string, mixed>|null */
    private static ?array $openapi = null;

    /** @return array<string, mixed> */
    private static function openapiSpec(): array
    {
        if (self::$openapi === null) {
            $path = dirname(__DIR__, 2) . '/schema/openapi.yaml';
            $parsed = Yaml::parseFile($path);
            if (!\is_array($parsed)) {
                throw new \RuntimeException('Invalid OpenAPI YAML: ' . $path);
            }
            self::$openapi = $parsed;
        }

        return self::$openapi;
    }

    /**
     * @return array<string, mixed>
     */
    private static function componentSchemas(): array
    {
        $spec = self::openapiSpec();
        $schemas = $spec['components']['schemas'] ?? null;
        if (!\is_array($schemas)) {
            throw new \RuntimeException('OpenAPI components.schemas is missing or invalid.');
        }

        return $schemas;
    }

    /**
     * @param array<string, mixed> $schemas
     */
    private static function resolveRefs(mixed $schemaNode, array $schemas): mixed
    {
        if (!\is_array($schemaNode)) {
            return $schemaNode;
        }

        if (isset($schemaNode['$ref']) && \is_string($schemaNode['$ref'])) {
            if (preg_match('~^#/components/schemas/([^/]+)$~', $schemaNode['$ref'], $m)) {
                $name = $m[1];
                if (!isset($schemas[$name]) || !\is_array($schemas[$name])) {
                    throw new \InvalidArgumentException('Unknown schema ref: ' . $schemaNode['$ref']);
                }

                return self::resolveRefs($schemas[$name], $schemas);
            }
        }

        $out = [];
        foreach ($schemaNode as $k => $v) {
            $out[$k] = self::resolveRefs($v, $schemas);
        }

        return $out;
    }

    private static function resolvedSchemaObject(string $rootSchemaName): \stdClass
    {
        $schemas = self::componentSchemas();
        if (!isset($schemas[$rootSchemaName]) || !\is_array($schemas[$rootSchemaName])) {
            throw new \InvalidArgumentException('OpenAPI schema not found: ' . $rootSchemaName);
        }

        $resolved = self::resolveRefs($schemas[$rootSchemaName], $schemas);
        if (!\is_array($resolved)) {
            throw new \RuntimeException('Resolved schema must be an array.');
        }

        $normalized = self::normalizeOpenApiNullable($resolved);

        return json_decode(json_encode($normalized, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * OpenAPI 3 {@code nullable: true} を、{@see justinrainbow/json-schema} が解釈できる type 配列へ正規化します。
     */
    private static function normalizeOpenApiNullable(mixed $node): mixed
    {
        if (!\is_array($node)) {
            return $node;
        }

        if (self::isListArray($node)) {
            $out = [];
            foreach ($node as $item) {
                $out[] = self::normalizeOpenApiNullable($item);
            }

            return $out;
        }

        /** @var array<string, mixed> $assoc */
        $assoc = $node;
        $nullable = ($assoc['nullable'] ?? null) === true;
        $scalarType = isset($assoc['type']) && \is_string($assoc['type']);
        if ($nullable && $scalarType) {
            $assoc['type'] = [$assoc['type'], 'null'];
            unset($assoc['nullable']);
        }

        foreach ($assoc as $k => $v) {
            $assoc[$k] = self::normalizeOpenApiNullable($v);
        }

        return $assoc;
    }

    /**
     * @param array<mixed> $array
     */
    private static function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, \count($array) - 1);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function assertSimilaritySuccessBody(TestCase $test, array $data): void
    {
        self::assertJsonMatchesSchema($test, $data, 'SimilarityResponse');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function assertEmbeddingSuccessBody(TestCase $test, array $data): void
    {
        self::assertJsonMatchesSchema($test, $data, 'EmbeddingResponse');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function assertErrorResponseBody(TestCase $test, array $data): void
    {
        self::assertJsonMatchesSchema($test, $data, 'ErrorResponse');
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function assertJsonMatchesSchema(TestCase $test, array $data, string $schemaName): void
    {
        $schema = self::resolvedSchemaObject($schemaName);
        $payload = json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $validator = new Validator();
        $validator->validate($payload, $schema);

        if ($validator->isValid()) {
            return;
        }

        $messages = [];
        foreach ($validator->getErrors() as $err) {
            $messages[] = ($err['pointer'] ?? '') . ': ' . ($err['message'] ?? '');
        }

        $test->fail('OpenAPI JSON Schema validation failed (' . $schemaName . "):\n" . implode("\n", $messages));
    }
}

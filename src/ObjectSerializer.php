<?php

namespace S2J\Similarity;

/**
 * Minimal serializer used by OpenAPI-generated DTOs.
 *
 * We intentionally keep this dependency-free so the library remains usable in
 * WordPress/shared-hosting environments.
 */
final class ObjectSerializer
{
    /**
     * Convert DTOs / arrays into json_encode-friendly values.
     */
    public static function sanitizeForSerialization(mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        if (is_scalar($data)) {
            return $data;
        }

        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $out[$k] = self::sanitizeForSerialization($v);
            }
            return $out;
        }

        if ($data instanceof \DateTimeInterface) {
            return $data->format(\DateTimeInterface::ATOM);
        }

        if ($data instanceof \JsonSerializable) {
            return self::sanitizeForSerialization($data->jsonSerialize());
        }

        if (is_object($data)) {
            // Fallback: expose public properties.
            $vars = get_object_vars($data);
            $out = [];
            foreach ($vars as $k => $v) {
                $out[$k] = self::sanitizeForSerialization($v);
            }
            return $out;
        }

        return $data;
    }
}

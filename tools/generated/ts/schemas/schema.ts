import { makeApi, Zodios, type ZodiosOptions } from "@zodios/core";
import { z } from "zod";

const SimilarityRequest = z
  .object({
    textA: z.string().min(1),
    textB: z.string().min(1),
    model: z.string().nullish(),
  })
  .passthrough();
const SimilarityResultData = z
  .object({ similarityScore: z.number().gte(0).lte(1) })
  .passthrough();
const ResponseMeta = z.object({}).partial().passthrough();
const SimilarityResponse = z
  .object({ data: SimilarityResultData, meta: ResponseMeta })
  .passthrough();
const ErrorObject = z
  .object({
    type: z.enum([
      "validation_error",
      "auth_error",
      "permission_error",
      "not_found",
      "timeout",
      "rate_limit",
      "internal_error",
      "provider_error",
      "network_error",
    ]),
    message: z.string(),
    details: z.object({}).partial().passthrough().nullish(),
  })
  .passthrough();
const ErrorResponse = z.object({ error: ErrorObject }).passthrough();
const EmbeddingRequest = z
  .object({ text: z.string().min(1), model: z.string().nullish() })
  .passthrough();
const EmbeddingResultData = z
  .object({ vector: z.array(z.number()), dimension: z.number().int().gte(1) })
  .passthrough();
const EmbeddingResponse = z
  .object({ data: EmbeddingResultData, meta: ResponseMeta })
  .passthrough();

export const schemas = {
  SimilarityRequest,
  SimilarityResultData,
  ResponseMeta,
  SimilarityResponse,
  ErrorObject,
  ErrorResponse,
  EmbeddingRequest,
  EmbeddingResultData,
  EmbeddingResponse,
};

const endpoints = makeApi([
  {
    method: "post",
    path: "/v1/embedding",
    alias: "generateEmbedding",
    requestFormat: "json",
    parameters: [
      {
        name: "body",
        type: "Body",
        schema: EmbeddingRequest,
      },
    ],
    response: EmbeddingResponse,
    errors: [
      {
        status: 400,
        description: `Validation error`,
        schema: ErrorResponse,
      },
      {
        status: 401,
        description: `Unauthorized`,
        schema: ErrorResponse,
      },
      {
        status: 403,
        description: `Forbidden`,
        schema: ErrorResponse,
      },
      {
        status: 429,
        description: `Rate limited`,
        schema: ErrorResponse,
      },
      {
        status: 500,
        description: `Internal server error`,
        schema: ErrorResponse,
      },
    ],
  },
  {
    method: "post",
    path: "/v1/similarity",
    alias: "calculateSimilarity",
    requestFormat: "json",
    parameters: [
      {
        name: "body",
        type: "Body",
        schema: SimilarityRequest,
      },
    ],
    response: SimilarityResponse,
    errors: [
      {
        status: 400,
        description: `Validation error`,
        schema: ErrorResponse,
      },
      {
        status: 401,
        description: `Unauthorized`,
        schema: ErrorResponse,
      },
      {
        status: 403,
        description: `Forbidden`,
        schema: ErrorResponse,
      },
      {
        status: 429,
        description: `Rate limited`,
        schema: ErrorResponse,
      },
      {
        status: 500,
        description: `Internal server error`,
        schema: ErrorResponse,
      },
    ],
  },
]);

export const api = new Zodios(endpoints);

export function createApiClient(baseUrl: string, options?: ZodiosOptions) {
  return new Zodios(baseUrl, endpoints, options);
}

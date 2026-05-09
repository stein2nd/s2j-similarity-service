/**
 * @packageDocumentation
 * Stable TS wrapper for S2J Similarity HTTP API — see docs/interfaces/sdk_spec.md
 * (「TypeScript SDK の最小実装スコープとエラー設計」).
 *
 * Generated OpenAPI clients under tools/generated/ts remain internal; this package is the public surface.
 */

export type { HttpClient } from './http.js'
export {
  createFetchHttpClient,
  stackHttpClient,
  withRetry,
  withTimeout,
  type RetryPolicy,
} from './http.js'

export type {
  ApiClient,
  CreateApiClientConfig,
  EmbeddingCallOptions,
  EmbeddingResponseBody,
  SimilarityCallOptions,
  SimilarityResponseBody,
} from './client.js'
export { SimilarityApiClient, createApiClient } from './client.js'

export type {
  AuthenticationError,
  ErrorType,
  InternalError,
  NetworkError,
  NotFoundError,
  ParsedApiErrorBody,
  PermissionError,
  ProviderError,
  RateLimitError,
  SDKDomainError,
  SDKError,
  SDKErrorShape,
  TimeoutError,
  ValidationError,
} from './errors.js'
export {
  AuthenticationErrorClass,
  InternalErrorClass,
  NetworkErrorClass,
  NotFoundErrorClass,
  PermissionErrorClass,
  ProviderErrorClass,
  RateLimitErrorClass,
  SDKErrorBase,
  TimeoutErrorClass,
  ValidationErrorClass,
  isSDKError,
  mapHttpStatusToError,
  parseErrorResponseBody,
  toSDKErrorException,
} from './errors.js'

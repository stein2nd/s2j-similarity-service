/**
 * Error model aligned with schema/openapi.yaml ErrorObject.type (snake_case).
 * Discriminated union is canonical; classes are runtime helpers.
 */

export type ErrorType =
  | 'validation_error'
  | 'auth_error'
  | 'permission_error'
  | 'not_found'
  | 'timeout'
  | 'rate_limit'
  | 'internal_error'
  | 'provider_error'
  | 'network_error'

const ERROR_TYPES = new Set<ErrorType>([
  'validation_error',
  'auth_error',
  'permission_error',
  'not_found',
  'timeout',
  'rate_limit',
  'internal_error',
  'provider_error',
  'network_error',
])

export interface SDKErrorShape {
  type: ErrorType
  message: string
  details?: unknown
}

export interface ValidationError extends SDKErrorShape {
  type: 'validation_error'
}

export interface AuthenticationError extends SDKErrorShape {
  type: 'auth_error'
}

export interface PermissionError extends SDKErrorShape {
  type: 'permission_error'
}

export interface NotFoundError extends SDKErrorShape {
  type: 'not_found'
}

export interface TimeoutError extends SDKErrorShape {
  type: 'timeout'
}

export interface RateLimitError extends SDKErrorShape {
  type: 'rate_limit'
}

export interface InternalError extends SDKErrorShape {
  type: 'internal_error'
}

export interface ProviderError extends SDKErrorShape {
  type: 'provider_error'
}

export interface NetworkError extends SDKErrorShape {
  type: 'network_error'
}

/** Canonical SDK error union (discriminated by `type`). */
export type SDKError =
  | ValidationError
  | AuthenticationError
  | PermissionError
  | NotFoundError
  | TimeoutError
  | RateLimitError
  | InternalError
  | ProviderError
  | NetworkError

/** Subset called out in sdk_spec.md — 「TypeScript SDK (@s2j/similarity-client)」 / エラー設計. */
export type SDKDomainError =
  | ValidationError
  | AuthenticationError
  | TimeoutError
  | RateLimitError
  | ProviderError

export class SDKErrorBase extends Error {
  readonly name = 'SDKError'

  constructor(
    public readonly type: ErrorType,
    message: string,
    public readonly details?: unknown,
  ) {
    super(message)
  }
}

export class ValidationErrorClass extends SDKErrorBase {
  readonly type: ValidationError['type'] = 'validation_error'

  constructor(message: string, details?: unknown) {
    super('validation_error', message, details)
  }
}

export class AuthenticationErrorClass extends SDKErrorBase {
  readonly type: AuthenticationError['type'] = 'auth_error'

  constructor(message: string, details?: unknown) {
    super('auth_error', message, details)
  }
}

export class PermissionErrorClass extends SDKErrorBase {
  readonly type: PermissionError['type'] = 'permission_error'

  constructor(message: string, details?: unknown) {
    super('permission_error', message, details)
  }
}

export class NotFoundErrorClass extends SDKErrorBase {
  readonly type: NotFoundError['type'] = 'not_found'

  constructor(message: string, details?: unknown) {
    super('not_found', message, details)
  }
}

export class TimeoutErrorClass extends SDKErrorBase {
  readonly type: TimeoutError['type'] = 'timeout'

  constructor(message: string, details?: unknown) {
    super('timeout', message, details)
  }
}

export class RateLimitErrorClass extends SDKErrorBase {
  readonly type: RateLimitError['type'] = 'rate_limit'

  constructor(message: string, details?: unknown) {
    super('rate_limit', message, details)
  }
}

export class InternalErrorClass extends SDKErrorBase {
  readonly type: InternalError['type'] = 'internal_error'

  constructor(message: string, details?: unknown) {
    super('internal_error', message, details)
  }
}

export class ProviderErrorClass extends SDKErrorBase {
  readonly type: ProviderError['type'] = 'provider_error'

  constructor(message: string, details?: unknown) {
    super('provider_error', message, details)
  }
}

export class NetworkErrorClass extends SDKErrorBase {
  readonly type: NetworkError['type'] = 'network_error'

  constructor(message: string, details?: unknown) {
    super('network_error', message, details)
  }
}

function errorTypeGuard(t: string): t is ErrorType {
  return ERROR_TYPES.has(t as ErrorType)
}

export function isSDKError(error: unknown): error is SDKError {
  if (!error || typeof error !== 'object') {
    return false
  }
  if (error instanceof SDKErrorBase) {
    return true
  }
  const rec = error as Record<string, unknown>
  return (
    typeof rec.type === 'string' &&
    errorTypeGuard(rec.type) &&
    typeof rec.message === 'string'
  )
}

export function toSDKErrorException(err: SDKError): SDKErrorBase {
  const { message, details } = err
  switch (err.type) {
    case 'validation_error':
      return new ValidationErrorClass(message, details)
    case 'auth_error':
      return new AuthenticationErrorClass(message, details)
    case 'permission_error':
      return new PermissionErrorClass(message, details)
    case 'not_found':
      return new NotFoundErrorClass(message, details)
    case 'timeout':
      return new TimeoutErrorClass(message, details)
    case 'rate_limit':
      return new RateLimitErrorClass(message, details)
    case 'internal_error':
      return new InternalErrorClass(message, details)
    case 'provider_error':
      return new ProviderErrorClass(message, details)
    case 'network_error':
      return new NetworkErrorClass(message, details)
    default: {
      const _x: never = err
      return _x
    }
  }
}

export interface ParsedApiErrorBody {
  type: ErrorType
  message: string
  details?: unknown
}

export function parseErrorResponseBody(json: unknown): ParsedApiErrorBody | null {
  if (!json || typeof json !== 'object') {
    return null
  }
  const root = json as Record<string, unknown>
  const error = root.error
  if (!error || typeof error !== 'object') {
    return null
  }
  const e = error as Record<string, unknown>
  if (typeof e.type !== 'string' || typeof e.message !== 'string') {
    return null
  }
  const type = errorTypeGuard(e.type) ? e.type : 'internal_error'
  return {
    type,
    message: e.message,
    details: e.details,
  }
}

export function mapHttpStatusToError(
  status: number,
  fallbackMessage: string,
  details?: unknown,
): SDKError {
  switch (status) {
    case 400:
      return { type: 'validation_error', message: fallbackMessage, details }
    case 401:
      return { type: 'auth_error', message: fallbackMessage, details }
    case 403:
      return { type: 'permission_error', message: fallbackMessage, details }
    case 404:
      return { type: 'not_found', message: fallbackMessage, details }
    case 429:
      return { type: 'rate_limit', message: fallbackMessage, details }
    case 503:
      return { type: 'internal_error', message: fallbackMessage, details }
    default:
      if (status >= 500) {
        return { type: 'internal_error', message: fallbackMessage, details }
      }
      return { type: 'internal_error', message: fallbackMessage, details }
  }
}

import { SDKErrorBase, toSDKErrorException, type SDKError } from './errors.js'

/**
 * Runtime-neutral HTTP surface (browser / edge / Node fetch).
 * @see docs/interfaces/sdk_spec.md — TypeScript SDK の最小実装スコープとエラー設計
 */
export interface HttpClient {
  fetch(input: RequestInfo, init?: RequestInit): Promise<Response>
}

export function createFetchHttpClient(fetchImpl: typeof fetch): HttpClient {
  return { fetch: fetchImpl }
}

function mergeAbortSignals(a: AbortSignal, b?: AbortSignal): AbortSignal {
  if (!b) {
    return a
  }
  if (typeof AbortSignal.any === 'function') {
    return AbortSignal.any([a, b])
  }
  const merged = new AbortController()
  const forward = (): void => {
    if (!merged.signal.aborted) {
      merged.abort()
    }
  }
  if (a.aborted || b.aborted) {
    forward()
    return merged.signal
  }
  a.addEventListener('abort', forward, { once: true })
  b.addEventListener('abort', forward, { once: true })
  return merged.signal
}

/** Applies an upper bound on how long a single fetch may run (AbortController). */
export function withTimeout(
  inner: HttpClient,
  timeoutMs: number,
): HttpClient {
  return {
    async fetch(input, init) {
      const controller = new AbortController()
      const timer = setTimeout(() => controller.abort(), timeoutMs)
      try {
        return await inner.fetch(input, {
          ...init,
          signal: mergeAbortSignals(controller.signal, init?.signal ?? undefined),
        })
      } finally {
        clearTimeout(timer)
      }
    },
  }
}

export interface RetryPolicy {
  /** Total attempts including the first try (e.g. 3 => up to 2 retries). */
  maxAttempts: number
  baseDelayMs: number
}

const defaultRetryPolicy: RetryPolicy = {
  maxAttempts: 3,
  baseDelayMs: 100,
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

function parseRetryAfterMs(header: string | null): number | null {
  if (!header) {
    return null
  }
  const n = Number.parseInt(header, 10)
  if (!Number.isNaN(n) && n >= 0) {
    return n * 1000
  }
  const date = Date.parse(header)
  if (!Number.isNaN(date)) {
    const delta = date - Date.now()
    return delta > 0 ? delta : 0
  }
  return null
}

function isRetriableHttpStatus(status: number): boolean {
  return status === 429 || status === 503
}

function classifyFetchFailure(err: unknown): SDKError {
  if (!err || typeof err !== 'object') {
    return { type: 'network_error', message: 'Network request failed', details: { cause: err } }
  }
  const anyErr = err as { name?: string; message?: string }
  if (anyErr.name === 'AbortError') {
    return {
      type: 'timeout',
      message: anyErr.message || 'Request aborted (timeout or cancellation)',
      details: { name: anyErr.name },
    }
  }
  return {
    type: 'network_error',
    message: anyErr.message || 'Network request failed',
    details: { name: anyErr.name, cause: err },
  }
}

function classifyThrowable(err: unknown): SDKError {
  if (err instanceof SDKErrorBase) {
    return { type: err.type, message: err.message, details: err.details }
  }
  return classifyFetchFailure(err)
}

/**
 * Lightweight retry: exponential backoff; idempotent-style POSTs only (caller responsibility).
 * Retries on transport failure, timeout (AbortError), HTTP 429, HTTP 503.
 */
export function withRetry(inner: HttpClient, policy: Partial<RetryPolicy> = {}): HttpClient {
  const { maxAttempts, baseDelayMs } = { ...defaultRetryPolicy, ...policy }

  return {
    async fetch(input, init) {
      let attempt = 0
      while (true) {
        try {
          const res = await inner.fetch(input, init)
          if (isRetriableHttpStatus(res.status) && attempt < maxAttempts - 1) {
            const retryAfter =
              res.status === 429 ? parseRetryAfterMs(res.headers.get('Retry-After')) : null
            const backoff = baseDelayMs * 2 ** attempt
            await sleep(retryAfter ?? backoff)
            attempt += 1
            continue
          }
          return res
        } catch (err) {
          const classified = classifyThrowable(err)
          const retriable =
            classified.type === 'network_error' || classified.type === 'timeout'
          if (!retriable || attempt >= maxAttempts - 1) {
            throw err instanceof SDKErrorBase ? err : toSDKErrorException(classified)
          }
          const backoff = baseDelayMs * 2 ** attempt
          await sleep(backoff)
          attempt += 1
        }
      }
    },
  }
}

export function stackHttpClient(
  base: HttpClient,
  options: { timeoutMs: number; retry?: Partial<RetryPolicy> },
): HttpClient {
  const withTime = withTimeout(base, options.timeoutMs)
  return withRetry(withTime, options.retry)
}

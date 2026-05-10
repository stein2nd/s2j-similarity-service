import {
  mapHttpStatusToError,
  parseErrorResponseBody,
  toSDKErrorException,
  type SDKError,
} from './errors.js'
import {
  createFetchHttpClient,
  stackHttpClient,
  type HttpClient,
} from './http.js'

export interface SimilarityResponseBody {
  data: { similarityScore: number }
  meta: Record<string, unknown>
}

export interface EmbeddingResponseBody {
  data: { vector: number[]; dimension: number }
  meta: Record<string, unknown>
}

/** Public, stable entry — docs/interfaces/sdk_spec.md §「TypeScript SDK (@s2j/similarity-client)」. */
export interface ApiClient {
  similarity(textA: string, textB: string, options?: SimilarityCallOptions): Promise<number>

  embedding(text: string, options?: EmbeddingCallOptions): Promise<EmbeddingResponseBody['data']>
}

export interface SimilarityCallOptions {
  model?: string | null
}

export interface EmbeddingCallOptions {
  model?: string | null
}

export interface CreateApiClientConfig {
  baseUrl: string
  apiKey?: string
  /** @default globalThis.fetch */
  fetchImpl?: typeof fetch
  /** @default 5000 */
  timeoutMs?: number
  /** @default 3 */
  maxAttempts?: number
  /** @default 100 */
  baseDelayMs?: number
  /** Pre-built stack (overrides fetchImpl / timeout / retry when set). */
  http?: HttpClient
}

function normalizeBaseUrl(baseUrl: string): string {
  return baseUrl.replace(/\/$/, '')
}

function assertNonEmpty(label: string, value: string): void {
  if (typeof value !== 'string' || value.length === 0) {
    throw toSDKErrorException({
      type: 'validation_error',
      message: `${label} must be a non-empty string`,
      details: { field: label },
    })
  }
}

function isSimilarityResponseBody(v: unknown): v is SimilarityResponseBody {
  if (!v || typeof v !== 'object') {
    return false
  }
  const d = (v as SimilarityResponseBody).data
  return (
    !!d &&
    typeof d === 'object' &&
    typeof (d as { similarityScore?: unknown }).similarityScore === 'number'
  )
}

function isEmbeddingResponseBody(v: unknown): v is EmbeddingResponseBody {
  if (!v || typeof v !== 'object') {
    return false
  }
  const d = (v as EmbeddingResponseBody).data
  if (!d || typeof d !== 'object') {
    return false
  }
  const vec = (d as { vector?: unknown }).vector
  const dim = (d as { dimension?: unknown }).dimension
  return Array.isArray(vec) && typeof dim === 'number'
}

async function readJson(res: Response): Promise<unknown> {
  const text = await res.text()
  if (!text) {
    return null
  }
  try {
    return JSON.parse(text) as unknown
  } catch {
    return null
  }
}

async function normalizeHttpFailure(res: Response): Promise<SDKError> {
  const json = await readJson(res)
  const parsed = parseErrorResponseBody(json)
  if (parsed) {
    return { type: parsed.type, message: parsed.message, details: parsed.details }
  }
  return mapHttpStatusToError(res.status, res.statusText || `HTTP ${res.status}`, {
    body: json,
  })
}

export class SimilarityApiClient implements ApiClient {
  constructor(
    private readonly http: HttpClient,
    private readonly baseUrl: string,
    private readonly apiKey?: string,
  ) {}

  async similarity(
    textA: string,
    textB: string,
    options?: SimilarityCallOptions,
  ): Promise<number> {
    assertNonEmpty('textA', textA)
    assertNonEmpty('textB', textB)
    const body = {
      textA,
      textB,
      model: options?.model ?? null,
    }
    const json = await this.postJson('/v1/similarity', body)
    if (!isSimilarityResponseBody(json)) {
      throw toSDKErrorException({
        type: 'internal_error',
        message: 'Unexpected similarity response shape',
        details: { json },
      })
    }
    return json.data.similarityScore
  }

  async embedding(
    text: string,
    options?: EmbeddingCallOptions,
  ): Promise<EmbeddingResponseBody['data']> {
    assertNonEmpty('text', text)
    const body = {
      text,
      model: options?.model ?? null,
    }
    const json = await this.postJson('/v1/embedding', body)
    if (!isEmbeddingResponseBody(json)) {
      throw toSDKErrorException({
        type: 'internal_error',
        message: 'Unexpected embedding response shape',
        details: { json },
      })
    }
    return json.data
  }

  private async postJson(path: string, body: unknown): Promise<unknown> {
    const url = `${normalizeBaseUrl(this.baseUrl)}${path}`
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(this.apiKey ? { Authorization: `Bearer ${this.apiKey}` } : {}),
    }
    const res = await this.http.fetch(url, {
      method: 'POST',
      headers,
      body: JSON.stringify(body),
    })
    if (!res.ok) {
      throw toSDKErrorException(await normalizeHttpFailure(res))
    }
    return readJson(res)
  }
}

export function createApiClient(config: CreateApiClientConfig): ApiClient {
  const baseUrl = normalizeBaseUrl(config.baseUrl)
  const http =
    config.http ??
    stackHttpClient(createFetchHttpClient(config.fetchImpl ?? globalThis.fetch.bind(globalThis)), {
      timeoutMs: config.timeoutMs ?? 5000,
      retry: {
        maxAttempts: config.maxAttempts ?? 3,
        baseDelayMs: config.baseDelayMs ?? 100,
      },
    })
  return new SimilarityApiClient(http, baseUrl, config.apiKey)
}

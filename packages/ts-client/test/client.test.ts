import { describe, expect, it } from 'vitest'
import { createApiClient } from '../src/client.js'
import type { HttpClient } from '../src/http.js'
import { isSDKError, SDKErrorBase } from '../src/errors.js'

function getHeader(headers: HeadersInit | undefined, name: string): string | null {
  if (!headers) {
    return null
  }
  if (headers instanceof Headers) {
    return headers.get(name)
  }
  if (Array.isArray(headers)) {
    const lower = name.toLowerCase()
    const row = headers.find(([k]) => k.toLowerCase() === lower)
    return row ? row[1] : null
  }
  const rec = headers as Record<string, string>
  return rec[name] ?? rec[name.toLowerCase()] ?? null
}

function mockHttp(
  handler: (url: string, init?: RequestInit) => Promise<Response>,
): { http: HttpClient; calls: Array<{ url: string; init?: RequestInit }> } {
  const calls: Array<{ url: string; init?: RequestInit }> = []
  const http: HttpClient = {
    async fetch(input, init) {
      const url = typeof input === 'string' ? input : input.url
      calls.push({ url, init })
      return handler(url, init)
    },
  }
  return { http, calls }
}

describe('SimilarityApiClient (mocked HttpClient)', () => {
  it('similarity: builds POST /v1/similarity with auth, headers, and JSON body', async () => {
    const { http, calls } = mockHttp(async () =>
      new Response(JSON.stringify({ data: { similarityScore: 0.42 }, meta: {} }), {
        status: 200,
        headers: { 'Content-Type': 'application/json' },
      }),
    )
    const client = createApiClient({
      baseUrl: 'https://api.example.com/',
      apiKey: 'test-token',
      http,
    })
    const score = await client.similarity('hello', 'world', { model: 'm1' })
    expect(score).toBe(0.42)
    expect(calls).toHaveLength(1)
    expect(calls[0].url).toBe('https://api.example.com/v1/similarity')
    expect(calls[0].init?.method).toBe('POST')
    const headers = calls[0].init?.headers
    expect(getHeader(headers, 'Content-Type')).toBe('application/json')
    expect(getHeader(headers, 'Authorization')).toBe('Bearer test-token')
    expect(JSON.parse(calls[0].init?.body as string)).toEqual({
      textA: 'hello',
      textB: 'world',
      model: 'm1',
    })
  })

  it('embedding: builds POST /v1/embedding', async () => {
    const { http, calls } = mockHttp(async () =>
      new Response(
        JSON.stringify({
          data: { vector: [1, 2], dimension: 2 },
          meta: {},
        }),
        { status: 200 },
      ),
    )
    const client = createApiClient({
      baseUrl: 'https://api.example.com',
      http,
    })
    const data = await client.embedding('text', { model: null })
    expect(data).toEqual({ vector: [1, 2], dimension: 2 })
    expect(calls[0].url.endsWith('/v1/embedding')).toBe(true)
    expect(JSON.parse(calls[0].init?.body as string)).toEqual({
      text: 'text',
      model: null,
    })
  })

  it('maps REST ErrorResponse JSON to SDKError (rate_limit)', async () => {
    const { http } = mockHttp(async () =>
      new Response(
        JSON.stringify({
          error: { type: 'rate_limit', message: 'Too many requests', details: { retry_after: 1 } },
        }),
        { status: 429 },
      ),
    )
    const client = createApiClient({
      baseUrl: 'https://api.example.com',
      http,
    })
    const err = await client.similarity('a', 'b').then(
      () => {
        throw new Error('expected rejection')
      },
      (e: unknown) => e,
    )
    expect(err).toBeInstanceOf(SDKErrorBase)
    expect(isSDKError(err)).toBe(true)
    expect((err as SDKErrorBase).type).toBe('rate_limit')
  })

  it('rejects empty inputs with validation_error', async () => {
    const { http } = mockHttp(async () => new Response('{}', { status: 200 }))
    const client = createApiClient({ baseUrl: 'https://api.example.com', http })
    await expect(client.similarity('', 'b')).rejects.toMatchObject({ type: 'validation_error' })
  })
})

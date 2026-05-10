import { describe, expect, it, vi } from 'vitest'
import { createFetchHttpClient, withRetry, withTimeout, type HttpClient } from '../src/http.js'
import { ValidationErrorClass } from '../src/errors.js'

describe('withTimeout', () => {
  it('aborts when the inner fetch does not settle in time', async () => {
    const inner: HttpClient = {
      fetch(_input, init) {
        return new Promise<Response>((_, reject) => {
          const signal = init?.signal
          if (!signal) {
            reject(new Error('expected AbortSignal'))
            return
          }
          signal.addEventListener('abort', () => {
            const err = new Error('Aborted')
            err.name = 'AbortError'
            reject(err)
          })
        })
      },
    }
    const wrapped = withTimeout(inner, 30)
    await expect(wrapped.fetch('http://localhost/slow')).rejects.toMatchObject({ name: 'AbortError' })
  })
})

describe('withRetry', () => {
  it('retries HTTP 503 until maxAttempts then returns the last response', async () => {
    vi.useFakeTimers()
    let calls = 0
    const inner: HttpClient = {
      fetch() {
        calls += 1
        return Promise.resolve(new Response(null, { status: 503 }))
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 3, baseDelayMs: 10 })
    const p = wrapped.fetch('http://localhost/r')
    await vi.runAllTimersAsync()
    const res = await p
    expect(res.status).toBe(503)
    expect(calls).toBe(3)
    vi.useRealTimers()
  })

  it('does not retry HTTP 401', async () => {
    let calls = 0
    const inner: HttpClient = {
      fetch() {
        calls += 1
        return Promise.resolve(new Response(null, { status: 401 }))
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 5, baseDelayMs: 10 })
    const res = await wrapped.fetch('http://localhost/auth')
    expect(res.status).toBe(401)
    expect(calls).toBe(1)
  })

  it('retries HTTP 429', async () => {
    vi.useFakeTimers()
    let calls = 0
    const inner: HttpClient = {
      fetch() {
        calls += 1
        if (calls < 2) {
          return Promise.resolve(new Response(null, { status: 429 }))
        }
        return Promise.resolve(new Response('ok', { status: 200 }))
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 3, baseDelayMs: 5 })
    const p = wrapped.fetch('http://localhost/limit')
    await vi.runAllTimersAsync()
    const res = await p
    expect(res.ok).toBe(true)
    expect(calls).toBe(2)
    vi.useRealTimers()
  })

  it('retries network failures then succeeds', async () => {
    vi.useFakeTimers()
    let calls = 0
    const inner: HttpClient = {
      async fetch() {
        calls += 1
        if (calls < 2) {
          throw new Error('ECONNRESET')
        }
        return new Response('{}', { status: 200 })
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 3, baseDelayMs: 5 })
    const p = wrapped.fetch('http://localhost/net')
    await vi.runAllTimersAsync()
    const res = await p
    expect(res.ok).toBe(true)
    expect(calls).toBe(2)
    vi.useRealTimers()
  })

  it('retries timeout (AbortError) then succeeds', async () => {
    vi.useFakeTimers()
    let calls = 0
    const inner: HttpClient = {
      async fetch(_input, init) {
        calls += 1
        if (calls < 2) {
          const signal = init?.signal
          return new Promise<Response>((_, reject) => {
            signal?.addEventListener('abort', () => {
              const err = new Error('Aborted')
              err.name = 'AbortError'
              reject(err)
            })
          })
        }
        return new Response('{}', { status: 200 })
      },
    }
    const innerWithTimeout = withTimeout(inner, 5000)
    const wrapped = withRetry(innerWithTimeout, { maxAttempts: 3, baseDelayMs: 5 })
    const p = wrapped.fetch('http://localhost/to')
    await vi.advanceTimersByTimeAsync(5000)
    await vi.runAllTimersAsync()
    const res = await p
    expect(res.ok).toBe(true)
    expect(calls).toBe(2)
    vi.useRealTimers()
  })

  it('does not retry HTTP 400', async () => {
    let calls = 0
    const inner: HttpClient = {
      fetch() {
        calls += 1
        return Promise.resolve(new Response(null, { status: 400 }))
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 5, baseDelayMs: 1 })
    const res = await wrapped.fetch('http://localhost/bad')
    expect(res.status).toBe(400)
    expect(calls).toBe(1)
  })

  it('rethrows non-retriable SDKError without backoff', async () => {
    const inner: HttpClient = {
      async fetch() {
        throw new ValidationErrorClass('bad')
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 5, baseDelayMs: 1 })
    await expect(wrapped.fetch('http://localhost/w')).rejects.toMatchObject({ type: 'validation_error' })
  })

  it('stops retrying retriable failures after maxAttempts', async () => {
    vi.useFakeTimers()
    let calls = 0
    const inner: HttpClient = {
      async fetch() {
        calls += 1
        throw new Error('network')
      },
    }
    const wrapped = withRetry(inner, { maxAttempts: 2, baseDelayMs: 1 })
    const p = wrapped.fetch('http://localhost/fail')
    const assertion = expect(p).rejects.toMatchObject({ type: 'network_error' })
    await vi.runAllTimersAsync()
    await assertion
    expect(calls).toBe(2)
    vi.useRealTimers()
  })
})

describe('createFetchHttpClient', () => {
  it('delegates to the given fetch implementation', async () => {
    const impl = vi.fn(async () => new Response('x', { status: 201 }))
    const client = createFetchHttpClient(impl as unknown as typeof fetch)
    const res = await client.fetch('http://localhost/z')
    expect(res.status).toBe(201)
    expect(impl).toHaveBeenCalledOnce()
  })
})

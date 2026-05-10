import { describe, expect, it } from 'vitest'
import {
  isSDKError,
  mapHttpStatusToError,
  parseErrorResponseBody,
  toSDKErrorException,
  ValidationErrorClass,
} from '../src/errors.js'

describe('parseErrorResponseBody', () => {
  it('parses OpenAPI-style error envelope into typed fields', () => {
    const json = {
      error: {
        type: 'rate_limit',
        message: 'Slow down',
        details: { unit: 'second' },
      },
    }
    const parsed = parseErrorResponseBody(json)
    expect(parsed).toEqual({
      type: 'rate_limit',
      message: 'Slow down',
      details: { unit: 'second' },
    })
  })

  it('returns null for malformed payloads', () => {
    expect(parseErrorResponseBody(null)).toBeNull()
    expect(parseErrorResponseBody({})).toBeNull()
    expect(parseErrorResponseBody({ error: 'x' })).toBeNull()
  })
})

describe('mapHttpStatusToError', () => {
  it('maps common HTTP statuses to SDK error kinds', () => {
    expect(mapHttpStatusToError(401, 'unauthorized').type).toBe('auth_error')
    expect(mapHttpStatusToError(403, 'no').type).toBe('permission_error')
    expect(mapHttpStatusToError(404, 'missing').type).toBe('not_found')
  })
})

describe('isSDKError', () => {
  it('narrows SDKErrorBase instances', () => {
    const err = new ValidationErrorClass('x')
    expect(isSDKError(err)).toBe(true)
  })

  it('narrows plain objects with type + message', () => {
    const plain = { type: 'timeout' as const, message: 'took too long' }
    expect(isSDKError(plain)).toBe(true)
  })

  it('rejects unrelated values', () => {
    expect(isSDKError(null)).toBe(false)
    expect(isSDKError(new Error('x'))).toBe(false)
  })
})

describe('toSDKErrorException', () => {
  it('returns a throwable with discriminated type', () => {
    const ex = toSDKErrorException({
      type: 'rate_limit',
      message: 'x',
      details: {},
    })
    expect(ex).toMatchObject({ type: 'rate_limit', message: 'x' })
    expect(isSDKError(ex)).toBe(true)
  })
})

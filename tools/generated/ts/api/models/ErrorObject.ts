/* generated using openapi-typescript-codegen -- do not edit */
/* istanbul ignore file */
/* tslint:disable */
/* eslint-disable */
export type ErrorObject = {
    /**
     * Stable machine-readable error category (snake_case). Aligns with docs/interfaces/rest_api_spec.md (e.g. validation_error, auth_error).
     *
     */
    type: ErrorObject.type;
    /**
     * Human-readable error message.
     */
    message: string;
    /**
     * Optional structured context (field hints, provider payloads, etc.).
     */
    details?: Record<string, any> | null;
};
export namespace ErrorObject {
    /**
     * Stable machine-readable error category (snake_case). Aligns with docs/interfaces/rest_api_spec.md (e.g. validation_error, auth_error).
     *
     */
    export enum type {
        VALIDATION_ERROR = 'validation_error',
        AUTH_ERROR = 'auth_error',
        PERMISSION_ERROR = 'permission_error',
        NOT_FOUND = 'not_found',
        TIMEOUT = 'timeout',
        RATE_LIMIT = 'rate_limit',
        INTERNAL_ERROR = 'internal_error',
        PROVIDER_ERROR = 'provider_error',
        NETWORK_ERROR = 'network_error',
    }
}


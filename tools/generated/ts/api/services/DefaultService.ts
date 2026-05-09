/* generated using openapi-typescript-codegen -- do not edit */
/* istanbul ignore file */
/* tslint:disable */
/* eslint-disable */
import type { EmbeddingRequest } from '../models/EmbeddingRequest';
import type { EmbeddingResponse } from '../models/EmbeddingResponse';
import type { SimilarityRequest } from '../models/SimilarityRequest';
import type { SimilarityResponse } from '../models/SimilarityResponse';
import type { CancelablePromise } from '../core/CancelablePromise';
import { OpenAPI } from '../core/OpenAPI';
import { request as __request } from '../core/request';
export class DefaultService {
    /**
     * Calculate semantic similarity
     * @param requestBody
     * @returns SimilarityResponse Success
     * @throws ApiError
     */
    public static calculateSimilarity(
        requestBody: SimilarityRequest,
    ): CancelablePromise<SimilarityResponse> {
        return __request(OpenAPI, {
            method: 'POST',
            url: '/v1/similarity',
            body: requestBody,
            mediaType: 'application/json',
            errors: {
                400: `Validation error`,
                401: `Unauthorized`,
                403: `Forbidden`,
                429: `Rate limited`,
                500: `Internal server error`,
            },
        });
    }
    /**
     * Generate embedding vector
     * @param requestBody
     * @returns EmbeddingResponse Success
     * @throws ApiError
     */
    public static generateEmbedding(
        requestBody: EmbeddingRequest,
    ): CancelablePromise<EmbeddingResponse> {
        return __request(OpenAPI, {
            method: 'POST',
            url: '/v1/embedding',
            body: requestBody,
            mediaType: 'application/json',
            errors: {
                400: `Validation error`,
                401: `Unauthorized`,
                403: `Forbidden`,
                429: `Rate limited`,
                500: `Internal server error`,
            },
        });
    }
}

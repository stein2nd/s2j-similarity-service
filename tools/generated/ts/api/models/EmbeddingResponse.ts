/* generated using openapi-typescript-codegen -- do not edit */
/* istanbul ignore file */
/* tslint:disable */
/* eslint-disable */
import type { EmbeddingResultData } from './EmbeddingResultData';
import type { ResponseMeta } from './ResponseMeta';
/**
 * HTTP success body for POST /v1/embedding (docs/interfaces/rest_api_spec.md).
 *
 */
export type EmbeddingResponse = {
    data: EmbeddingResultData;
    meta: ResponseMeta;
};


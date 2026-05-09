/* generated using openapi-typescript-codegen -- do not edit */
/* istanbul ignore file */
/* tslint:disable */
/* eslint-disable */
import type { ResponseMeta } from './ResponseMeta';
import type { SimilarityResultData } from './SimilarityResultData';
/**
 * HTTP success body for POST /v1/similarity (docs/interfaces/rest_api_spec.md).
 *
 */
export type SimilarityResponse = {
    data: SimilarityResultData;
    meta: ResponseMeta;
};


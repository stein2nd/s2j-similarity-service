/* generated using openapi-typescript-codegen -- do not edit */
/* istanbul ignore file */
/* tslint:disable */
/* eslint-disable */
export type SimilarityRequest = {
    /**
     * Input text A (must not be empty).
     */
    textA: string;
    /**
     * Input text B (must not be empty).
     */
    textB: string;
    /**
     * Optional embedding model identifier.
     */
    model?: string | null;
};


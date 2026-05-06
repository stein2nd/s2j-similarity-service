<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Controllers;

use S2J\Similarity\Adapters\Http\WordPress\Auth\BearerTokenAuth;
use S2J\Similarity\Adapters\Http\WordPress\Error\ErrorMapper;
use S2J\Similarity\Adapters\Http\WordPress\Http\RequestReader;
use S2J\Similarity\Adapters\Http\WordPress\Response\ResponseFactory;
use S2J\Similarity\Adapters\Http\WordPress\Validation\RequestValidator;
use S2J\Similarity\Application\SimilarityService;
use S2J\Similarity\Contracts\Errors\DomainError;
use S2J\Similarity\Contracts\Errors\InternalError;

final class SimilarityController
{
    public function __construct(
        private readonly SimilarityService $service,
        private readonly BearerTokenAuth $auth
    ) {}

    public function permission(mixed $request): mixed
    {
        return $this->auth->permission($request);
    }

    public function handle(mixed $request): mixed
    {
        try {
            $body = RequestReader::jsonBody($request);

            $textA = RequestValidator::requireNonEmptyString($body, 'textA');
            $textB = RequestValidator::requireNonEmptyString($body, 'textB');
            $model = RequestValidator::optionalString($body, 'model');

            $score = $this->service->similarity($textA, $textB, $model);

            return ResponseFactory::ok([
                'data' => [
                    'similarityScore' => $score,
                ],
                'meta' => (object)[],
            ]);
        } catch (DomainError $e) {
            $mapped = ErrorMapper::toErrorResponse($e);
            return ResponseFactory::json($mapped['body'], $mapped['status']);
        } catch (\Throwable $e) {
            $mapped = ErrorMapper::toErrorResponse(new InternalError('Internal error', [], $e));
            return ResponseFactory::json($mapped['body'], $mapped['status']);
        }
    }
}

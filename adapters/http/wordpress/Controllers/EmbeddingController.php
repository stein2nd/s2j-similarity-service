<?php

namespace S2J\Similarity\Adapters\Http\WordPress\Controllers;

use S2J\Similarity\Adapters\Http\WordPress\Auth\BearerTokenAuth;
use S2J\Similarity\Adapters\Http\WordPress\Error\ErrorMapper;
use S2J\Similarity\Adapters\Http\WordPress\Http\RequestReader;
use S2J\Similarity\Adapters\Http\WordPress\Response\ResponseFactory;
use S2J\Similarity\Adapters\Http\WordPress\Validation\RequestValidator;
use S2J\Similarity\Application\EmbeddingService;
use S2J\Similarity\Contracts\Errors\DomainError;
use S2J\Similarity\Contracts\Errors\InternalError;

final class EmbeddingController
{
    public function __construct(
        private readonly EmbeddingService $service,
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

            $text = RequestValidator::requireNonEmptyString($body, 'text');
            $model = RequestValidator::optionalString($body, 'model');

            $embedding = $this->service->embed($text, $model);

            return ResponseFactory::ok([
                'data' => [
                    'vector' => $embedding->vector,
                    'dimension' => $embedding->dimension,
                ],
                'meta' => (object)[],
            ]);
        } catch (DomainError $e) {
            $mapped = ErrorMapper::toErrorResponse($e);
            return ResponseFactory::json($mapped['body'], $mapped['status'], $mapped['headers']);
        } catch (\Throwable $e) {
            $mapped = ErrorMapper::toErrorResponse(new InternalError('Internal error', [], $e));
            return ResponseFactory::json($mapped['body'], $mapped['status'], $mapped['headers']);
        }
    }
}

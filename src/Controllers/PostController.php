<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Contracts\PostServiceInterface;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StateTransitionException;

class PostController
{
    public function __construct(
        private PostServiceInterface $service
    ) {}

    public function index($request, $response): object
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = (int) ($queryParams['page'] ?? 1);
            $perPage = (int) ($queryParams['per_page'] ?? 10);

            $result = $this->service->listPosts($page, $perPage, $queryParams);

            return $response
                ->withStatus(200)
                ->withJson(['data' => $result]);
        } catch (ValidationException $e) {
            return $response
                ->withStatus(400)
                ->withJson([
                    'error' => $e->getMessage(),
                    'details' => $e->getErrors()
                ]);
        }
    }

    public function show($request, $response, array $args): object
    {
        try {
            $post = $this->service->getPost((int) $args['id']);

            // 記錄文章瀏覽
            $userIp = $request->getAttribute('ip_address');
            $userId = $request->getAttribute('user_id');
            $this->service->recordView($post->getId(), $userIp, $userId);

            return $response
                ->withStatus(200)
                ->withJson(['data' => $post->toArray()]);
        } catch (NotFoundException $e) {
            return $response
                ->withStatus(404)
                ->withJson(['error' => $e->getMessage()]);
        }
    }

    public function store($request, $response): object
    {
        try {
            $data = $request->getParsedBody();
            $post = $this->service->createPost($data);

            return $response
                ->withStatus(201)
                ->withJson(['data' => $post->toArray()]);
        } catch (ValidationException $e) {
            return $response
                ->withStatus(400)
                ->withJson([
                    'error' => $e->getMessage(),
                    'details' => $e->getErrors()
                ]);
        }
    }

    public function update($request, $response, array $args): object
    {
        try {
            $data = $request->getParsedBody();
            $post = $this->service->updatePost((int) $args['id'], $data);

            return $response
                ->withStatus(200)
                ->withJson(['data' => $post->toArray()]);
        } catch (NotFoundException $e) {
            return $response
                ->withStatus(404)
                ->withJson(['error' => $e->getMessage()]);
        } catch (ValidationException $e) {
            return $response
                ->withStatus(400)
                ->withJson([
                    'error' => $e->getMessage(),
                    'details' => $e->getErrors()
                ]);
        } catch (StateTransitionException $e) {
            return $response
                ->withStatus(422)
                ->withJson(['error' => $e->getMessage()]);
        }
    }

    public function destroy($request, $response, array $args): object
    {
        try {
            $this->service->deletePost((int) $args['id']);
            return $response->withStatus(204);
        } catch (NotFoundException $e) {
            return $response
                ->withStatus(404)
                ->withJson(['error' => $e->getMessage()]);
        } catch (StateTransitionException $e) {
            return $response
                ->withStatus(422)
                ->withJson(['error' => $e->getMessage()]);
        }
    }

    public function updatePinStatus($request, $response, array $args): object
    {
        try {
            $data = $request->getParsedBody();
            $isPinned = (bool) ($data['is_pinned'] ?? false);

            $this->service->setPinned((int) $args['id'], $isPinned);
            return $response->withStatus(204);
        } catch (NotFoundException $e) {
            return $response
                ->withStatus(404)
                ->withJson(['error' => $e->getMessage()]);
        } catch (StateTransitionException $e) {
            return $response
                ->withStatus(422)
                ->withJson(['error' => $e->getMessage()]);
        }
    }
}

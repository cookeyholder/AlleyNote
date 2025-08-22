<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Contracts\PostServiceInterface;
use App\Services\Security\Contracts\XssProtectionServiceInterface;
use App\Services\Security\Contracts\CsrfProtectionServiceInterface;
use App\DTOs\Post\CreatePostDTO;
use App\DTOs\Post\UpdatePostDTO;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StateTransitionException;
use App\Exceptions\CsrfTokenException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PostController
{
    private const CSRF_HEADER = 'X-CSRF-TOKEN';

    public function __construct(
        private PostServiceInterface $service,
        private XssProtectionServiceInterface $xssProtection,
        private CsrfProtectionServiceInterface $csrfProtection
    ) {}

    private function validateCsrfToken(Request $request): void
    {
        $token = $request->getHeaderLine(self::CSRF_HEADER);
        $this->csrfProtection->validateToken($token);
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = (int) ($queryParams['page'] ?? 1);
            $perPage = (int) ($queryParams['per_page'] ?? 10);

            $result = $this->service->listPosts($page, $perPage, $queryParams);

            return $this->jsonResponse($response, ['data' => $result]);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage(),
                'details' => $e->getErrors()
            ], 400);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $post = $this->service->findById((int) $args['id']);

            // 記錄文章瀏覽
            $userIp = $request->getAttribute('ip_address');
            $userId = $request->getAttribute('user_id');
            $this->service->recordView($post->getId(), $userIp, $userId);

            return $this->jsonResponse($response, ['data' => $post->toArray()]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $this->validateCsrfToken($request);

            $data = $request->getParsedBody();
            $data = $this->xssProtection->cleanArray($data, ['title', 'content']);

            // 添加使用者資訊
            $data['user_id'] = $request->getAttribute('user_id', 1); // 預設使用者 ID
            $data['user_ip'] = $request->getAttribute('ip_address', '127.0.0.1');

            // 建立 DTO
            $dto = new CreatePostDTO($data);
            $post = $this->service->createPost($dto);

            return $this->jsonResponse($response, ['data' => $post->toArray()], 201)
                ->withHeader(self::CSRF_HEADER, $this->csrfProtection->generateToken());
        } catch (CsrfTokenException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage(),
                'details' => $e->getErrors()
            ], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, [
                'error' => '資料驗證失敗',
                'details' => ['validation' => [$e->getMessage()]]
            ], 400);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $this->validateCsrfToken($request);

            $data = $request->getParsedBody();
            $data = $this->xssProtection->cleanArray($data, ['title', 'content']);

            // 建立更新 DTO
            $dto = new UpdatePostDTO($data);
            $post = $this->service->updatePost((int) $args['id'], $dto);

            return $this->jsonResponse($response, ['data' => $post->toArray()]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage(),
                'details' => $e->getErrors()
            ], 400);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, [
                'error' => '資料驗證失敗',
                'details' => ['validation' => [$e->getMessage()]]
            ], 400);
        } catch (StateTransitionException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 422);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $this->service->deletePost((int) $args['id']);
            return $response->withStatus(204);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (StateTransitionException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 422);
        }
    }

    public function updatePinStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            $isPinned = (bool) ($data['is_pinned'] ?? false);

            $this->service->setPinned((int) $args['id'], $isPinned);
            return $response->withStatus(204);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 404);
        } catch (StateTransitionException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register($request, $response): object
    {
        try {
            $data = $request->getParsedBody();
            $user = $this->authService->register($data);

            return $response
                ->withStatus(201)
                ->withJson([
                    'success' => true,
                    'message' => '註冊成功',
                    'data' => $user
                ]);
        } catch (\InvalidArgumentException $e) {
            return $response
                ->withStatus(400)
                ->withJson([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
        } catch (\Exception $e) {
            return $response
                ->withStatus(500)
                ->withJson([
                    'success' => false,
                    'error' => '系統發生錯誤'
                ]);
        }
    }

    public function login($request, $response): object
    {
        try {
            $credentials = $request->getParsedBody();
            $result = $this->authService->login($credentials);

            if (!$result['success']) {
                return $response
                    ->withStatus(401)
                    ->withJson($result);
            }

            return $response
                ->withStatus(200)
                ->withJson($result);
        } catch (\Exception $e) {
            return $response
                ->withStatus(500)
                ->withJson([
                    'success' => false,
                    'error' => '系統發生錯誤'
                ]);
        }
    }

    public function logout($request, $response): object
    {
        return $response
            ->withStatus(200)
            ->withJson([
                'success' => true,
                'message' => '登出成功'
            ]);
    }
}

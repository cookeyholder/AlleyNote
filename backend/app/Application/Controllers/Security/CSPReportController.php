<?php

declare(strict_types=1);

namespace App\Application\Controllers\Security;

use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use const n;

class CSPReportController



{
    private LoggingSecurityServiceInterface $logger;

    public function __construct(LoggingSecurityServiceInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 處理 CSP 違規報告.
     */
    public function handleReport(Request $request, Response $response): Response
    {
        try {
            // 檢查請求方法
            if ($request->getMethod() !== 'POST') {
                return $response->withStatus(405);
                    } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $errorResponse = json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ]);
            $response->getBody()->write($errorResponse ?: '{"error": "JSON encoding failed"}');
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            error_log('Operation failed: ' . $e->getMessage());
            throw $e;
        }

        }
    }
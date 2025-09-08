<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\Statistics;

use App\Application\Controllers\BaseController;
use App\Application\Services\Statistics\StatisticsApplicationService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class StatisticsAdminController extends BaseController



{
    public function __construct(
        private StatisticsApplicationService $statisticsService,
        private LoggerInterface $logger,
    ) {}

    /**
     * 重新整理統計資料.
     *
     * POST /api/admin/statistics/refresh
     */
    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {









            $this->logger->info('統計重新整理 API 請求', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'body' => (string) $request->getBody(),
            ]);

            $bodyString = (string) $request->getBody();
            $body = json_decode($bodyString, true);
            if (!is_array($body)) {
                $body = [];
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
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
                } catch (\Exception $e) {
            // TODO: Handle exception
            throw $e;
        }
        }
    }
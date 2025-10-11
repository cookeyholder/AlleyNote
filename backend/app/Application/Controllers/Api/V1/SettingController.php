<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Setting\Services\SettingManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Helpers\TimezoneHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 系統設定管理 Controller.
 */
class SettingController
{
    public function __construct(
        private readonly SettingManagementService $settingManagementService,
    ) {}

    /**
     * 取得系統設定列表.
     *
     * GET /api/settings
     */
    public function index(Request $request, Response $response): Response
    {
        $settings = $this->settingManagementService->getAllSettings();

        $responseData = json_encode([
            'success' => true,
            'data' => $settings,
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * 取得單一設定.
     *
     * GET /api/settings/{key}
     */
    public function show(Request $request, Response $response): Response
    {
        try {
            $keyAttr = $request->getAttribute('key');
            if (!is_string($keyAttr)) {
                throw new InvalidArgumentException('Invalid setting key');
            }
            $key = $keyAttr;
            $setting = $this->settingManagementService->getSetting($key);

            $responseData = json_encode([
                'success' => true,
                'data' => $setting,
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }

    /**
     * 批量更新系統設定.
     *
     * PUT /api/settings
     */
    public function update(Request $request, Response $response): Response
    {
        try {
            $data = json_decode((string) $request->getBody(), true);
            /** @var array<string, mixed> */
            $settings = is_array($data) ? $data : [];

            $result = $this->settingManagementService->updateSettings($settings);

            $responseData = json_encode([
                'success' => true,
                'data' => $result,
                'message' => '系統設定更新成功',
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 更新單一設定.
     *
     * PUT /api/settings/{key}
     */
    public function updateSingle(Request $request, Response $response): Response
    {
        try {
            $keyAttr = $request->getAttribute('key');
            if (!is_string($keyAttr)) {
                throw new InvalidArgumentException('Invalid setting key');
            }
            $key = $keyAttr;

            $data = json_decode((string) $request->getBody(), true);
            if (!is_array($data)) {
                $data = [];
            }
            $value = $data['value'] ?? null;

            $setting = $this->settingManagementService->updateSetting($key, $value);

            $responseData = json_encode([
                'success' => true,
                'data' => $setting,
                'message' => '設定更新成功',
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (NotFoundException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        } catch (ValidationException $e) {
            $responseData = json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ]);

            $response->getBody()->write($responseData ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }
    }

    /**
     * 取得時區設定資訊
     *
     * GET /api/settings/timezone/info
     */
    public function getTimezoneInfo(Request $request, Response $response): Response
    {
        $timezone = TimezoneHelper::getSiteTimezone();
        $offset = TimezoneHelper::getTimezoneOffset();
        $currentTime = TimezoneHelper::nowSiteTimezone();
        $commonTimezones = TimezoneHelper::getCommonTimezones();

        $responseData = json_encode([
            'success' => true,
            'data' => [
                'timezone' => $timezone,
                'offset' => $offset,
                'current_time' => $currentTime,
                'common_timezones' => $commonTimezones,
            ],
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}

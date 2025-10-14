<?php

declare(strict_types=1);

namespace App\Application\Controllers\Api\V1;

use App\Domains\Setting\Services\SettingManagementService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Helpers\TimezoneHelper;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 系統設定管理 Controller.
 */
#[OA\Tag(
    name: 'Settings',
    description: 'System settings management endpoints',
)]
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
    #[OA\Get(
        path: '/api/settings',
        operationId: 'listSettings',
        summary: '取得系統設定列表',
        description: '取得所有系統設定項目及其值',
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得設定列表',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'string'),
                            example: [
                                'site_name' => 'AlleyNote',
                                'site_timezone' => 'Asia/Taipei',
                                'maintenance_mode' => 'false',
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
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
    #[OA\Get(
        path: '/api/settings/{key}',
        operationId: 'getSettingByKey',
        summary: '取得單一設定',
        description: '根據設定鍵取得特定設定值',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                in: 'path',
                description: '設定鍵名',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'site_name'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得設定',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'key', type: 'string', example: 'site_name'),
                                new OA\Property(property: 'value', type: 'string', example: 'AlleyNote'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '設定不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '設定不存在'),
                    ],
                ),
            ),
        ],
    )]
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
    #[OA\Put(
        path: '/api/settings',
        operationId: 'updateSettings',
        summary: '批量更新系統設定',
        description: '一次更新多個系統設定項目',
        tags: ['Settings'],
        requestBody: new OA\RequestBody(
            description: '設定鍵值對',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                additionalProperties: new OA\AdditionalProperties(type: 'string'),
                example: [
                    'site_name' => 'My Site',
                    'site_timezone' => 'Asia/Tokyo',
                    'maintenance_mode' => 'false',
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '設定更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '系統設定更新成功'),
                        new OA\Property(property: 'data', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '資料驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
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
    #[OA\Put(
        path: '/api/settings/{key}',
        operationId: 'updateSingleSetting',
        summary: '更新單一設定',
        description: '更新指定的系統設定值',
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                in: 'path',
                description: '設定鍵名',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'site_name'),
            ),
        ],
        requestBody: new OA\RequestBody(
            description: '設定值',
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'value', type: 'string', description: '新的設定值', example: 'My New Site Name'),
                ],
                required: ['value'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '設定更新成功',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '設定更新成功'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'key', type: 'string', example: 'site_name'),
                                new OA\Property(property: 'value', type: 'string', example: 'My New Site Name'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 404,
                description: '設定不存在',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '設定不存在'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: '資料驗證失敗',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: '資料驗證失敗'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ],
                ),
            ),
        ],
    )]
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
     * 取得時區設定資訊.
     *
     * GET /api/settings/timezone/info
     */
    #[OA\Get(
        path: '/api/settings/timezone/info',
        operationId: 'getTimezoneInfo',
        summary: '取得時區資訊',
        description: '取得系統時區設定及常用時區列表',
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: '成功取得時區資訊',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'timezone', type: 'string', example: 'Asia/Taipei'),
                                new OA\Property(property: 'offset', type: 'string', example: '+08:00'),
                                new OA\Property(property: 'current_time', type: 'string', format: 'date-time'),
                                new OA\Property(
                                    property: 'common_timezones',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['Asia/Taipei', 'Asia/Tokyo', 'America/New_York'],
                                ),
                            ],
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function getTimezoneInfo(Request $request, Response $response): Response
    {
        $timezone = TimezoneHelper::getSiteTimezone();
        $offset = TimezoneHelper::getTimezoneOffset();
        $currentTime = TimezoneHelper::nowSiteTimezone();
        $allTimezones = TimezoneHelper::getAllTimezones(); // 使用全球所有時區

        $responseData = json_encode([
            'success' => true,
            'data' => [
                'timezone' => $timezone,
                'offset' => $offset,
                'current_time' => $currentTime,
                'common_timezones' => $allTimezones, // 返回全部 419 個時區
            ],
        ]);

        $response->getBody()->write($responseData ?: '');

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}

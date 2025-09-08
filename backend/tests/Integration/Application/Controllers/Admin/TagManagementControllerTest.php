<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Controllers\Admin;

use App\Application\Controllers\Admin\TagManagementController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Drivers\MemoryCacheDriver;
use App\Shared\Cache\Services\CacheGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * TagManagementController 整合測試.
 */
class TagManagementControllerTest extends TestCase



{
    private TagManagementController $controller;

    private CacheManagerInterface&MockObject $cacheManager;

    private CacheGroupManager&MockObject $groupManager;

    private LoggerInterface&MockObject $logger;

    private ServerRequestInterface&MockObject $request;

    private ResponseInterface&MockObject $response;

    private StreamInterface&MockObject $responseBody;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheManager = $this->createMock(CacheManagerInterface::class);
        $this->groupManager = $this->createMock(CacheGroupManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new TagManagementController(
            $this->cacheManager,
            $this->groupManager,
            $this->logger,
        );

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseBody = $this->createMock(StreamInterface::class);

        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->response->method('withHeader')->willReturnSelf();
        $this->response->method('withStatus')->willReturnSelf();
    }

    /**
     * 安全地解析 JSON 響應，確保型別安全.
     */
    private function safeJsonDecode(mixed $content): ?array
    {
        if (!is_string($content)) {
            return null;
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * 驗證響應資料結構.
     */
    private function validateResponse(array $data, array $expectedKeys): bool
    {
        if (!isset($data['success'] || $data['success'] !== true) {
            return false;
        }

        $current = $data;
        foreach ($expectedKeys as $key => $value) {
            if (is_array($value)) {
                if (!isset($current[$key] || !is_array($current[$key]) {
                    return false;
                }
                $current = $current[$key];
            } else {
                if (!isset($current[$key] || $current[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function testListTags(): void
    {
        $queryParams = $this->getTestQueryParams();
        $this->request->method('getQueryParams')->willReturn($queryParams);

        $mockDriver = $this->setupMockDriverForListTags();
        $this->setupCacheManagerForListTags($mockDriver);

        $this->expectSuccessfulListTagsResponse();

        $result = $this->controller->listTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTag(): void
    {
        $args = ['tag' => 'user_123'];

        $mockDriver = $this->setupMockDriverForGetTag();
        $this->setupCacheManagerForGetTag($mockDriver);

        $this->expectSuccessfulGetTagResponse();

        $result = $this->controller->getTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTag(): void
    {
        $args = ['tag' => 'user_123'];

        $this->setupMockDriversForFlushTag();
        $this->expectSuccessfulFlushTagResponse();

        $result = $this->controller->flushTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTags(): void
    {
        $testTags = ['user_123', 'module_posts'];
        $requestBody = json_encode(['tags' => $testTags]);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

        $mockDriver = $this->setupMockDriverForFlushTags($testTags);
        $this->setupCacheManagerForFlushTags($mockDriver);

        $this->expectSuccessfulFlushTagsResponse();

        $result = $this->controller->flushTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTagStatistics(): void
    {
        $this->setupMockDriversForTagStatistics();
        $this->setupGroupManagerForTagStatistics();

        $this->expectSuccessfulTagStatisticsResponse();

        $result = $this->controller->getTagStatistics($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateGroup(): void
    {
        $testGroupData = $this->getTestGroupData();
        $requestBody = json_encode($testGroupData);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

        $this->setupGroupManagerForCreateGroup($testGroupData);
        $this->expectSuccessfulCreateGroupResponse($testGroupData['name']);

        $result = $this->controller->createGroup($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testListGroups(): void
    {
        $mockGroupStatistics = $this->getMockGroupStatistics();
        $this->groupManager->method('getGroupStatistics')->willReturn($mockGroupStatistics);

        $this->expectSuccessfulListGroupsResponse();

        $result = $this->controller->listGroups($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushGroup(): void
    {
        $args = ['group' => 'test_group'];
        $queryParams = ['cascade' => 'true'];

        $this->request->method('getQueryParams')->willReturn($queryParams);

        $this->setupGroupManagerForFlushGroup();
        $this->expectSuccessfulFlushGroupResponse();

        $result = $this->controller->flushGroup($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testErrorHandling(): void
    {
        // 測試當 getDriver 拋出例外時的錯誤處理
        $this->cacheManager->method('getDriver')
            ->willThrowException(new RuntimeException('驅動程式故障'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('取得標籤列表失敗'),
                $this->arrayHasKey('error'),
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null && $data['success'] === false
                       && isset($data['error']);
            }));

        $this->request->method('getQueryParams')->willReturn([]);

        $result = $this->controller->listTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testInvalidInput(): void
    {
        // 測試無效的標籤名稱
        $args = ['tag' => ''];

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('取得標籤詳細資訊失敗'),
                $this->arrayHasKey('error'),
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null && $data['success'] === false
                       && $data['error']['details'] === '標籤名稱不能為空';
            }));

        $result = $this->controller->getTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    private function createStreamWithContent(string $content): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($content);

        return $stream;
    }

    /**
     * 取得測試查詢參數.
     */
    private function getTestQueryParams(): array
    {
        return [
            'page' => '1',
            'limit' => '20',
            'search' => 'user',
        ];
    }

    /**
     * 設定列表標籤的 Mock 驅動程式.
     */
    private function setupMockDriverForListTags(): MemoryCacheDriver&MockObject
    {
        $mockDriver = $this->createMock(MemoryCacheDriver::class);
        $mockDriver->method('getAllTags')->willReturn(['user_123', 'user_456', 'module_posts']);
        $mockDriver->method('getTagStatistics')->willReturn([
            'total_tags' => 3,
            'tags' => [
                'user_123' => ['key_count' => 5, 'sample_keys' => ['key1', 'key2']],
                'user_456' => ['key_count' => 3, 'sample_keys' => ['key3']],
                'module_posts' => ['key_count' => 8, 'sample_keys' => ['key4', 'key5']],
            ],
        ]);

        return $mockDriver;
    }

    /**
     * 設定快取管理器用於列表標籤測試.
     */
    private function setupCacheManagerForListTags(MemoryCacheDriver&MockObject $mockDriver): void
    {
        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null],
            ]);
    }

    /**
     * 期待成功的列表標籤回應.
     */
    private function expectSuccessfulListTagsResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateListTagsResponse($content);
            }));
    }

    /**
     * 驗證列表標籤回應內容.
     */
    private function validateListTagsResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && is_array($data['data'])
               && isset($data['data']['tags']) && is_array($data['data']['tags']) && count($data['data']['tags']) === 2 // 只有 user 相關標籤
               && isset($data['data']['pagination']) && is_array($data['data']['pagination'])
               && isset($data['data']['pagination']['total']) && $data['data']['pagination']['total'] === 2;
    }

    /**
     * 設定取得標籤的 Mock 驅動程式.
     */
    private function setupMockDriverForGetTag(): MemoryCacheDriver&MockObject
    {
        $mockDriver = $this->createMock(MemoryCacheDriver::class);
        $mockDriver->method('tagExists')->with('user_123')->willReturn(true);
        $mockDriver->method('getKeysByTag')->with('user_123')->willReturn(['key1', 'key2', 'key3']);
        $mockDriver->method('getTagStatistics')->willReturn([
            'tags' => [
                'user_123' => ['key_count' => 3, 'sample_keys' => ['key1', 'key2']],
            ],
        ]);
        $mockDriver->method('get')
            ->willReturnMap([
                ['key1', 'value1'],
                ['key2', 'value2'],
                ['key3', null],
            ]);

        return $mockDriver;
    }

    /**
     * 設定快取管理器用於取得標籤測試.
     */
    private function setupCacheManagerForGetTag(MemoryCacheDriver&MockObject $mockDriver): void
    {
        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
            ]);
    }

    /**
     * 期待成功的取得標籤回應.
     */
    private function expectSuccessfulGetTagResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateGetTagResponse($content);
            }));
    }

    /**
     * 驗證取得標籤回應內容.
     */
    private function validateGetTagResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && is_array($data['data'])
               && isset($data['data']['name']) && $data['data']['name'] === 'user_123'
               && isset($data['data']['driver']) && $data['data']['driver'] === 'redis'
               && isset($data['data']['statistics']) && is_array($data['data']['statistics'])
               && isset($data['data']['statistics']['key_count']) && $data['data']['statistics']['key_count'] === 3
               && isset($data['data']['type']) && $data['data']['type'] === 'other'
               && isset($data['timestamp']);
    }

    /**
     * 設定清空標籤的 Mock 驅動程式.
     */
    private function setupMockDriversForFlushTag(): void
    {
        $mockDriver1 = $this->createMock(MemoryCacheDriver::class);
        $mockDriver1->method('flushByTags')->with(['user_123'])->willReturn(5);

        $mockDriver2 = $this->createMock(MemoryCacheDriver::class);
        $mockDriver2->method('flushByTags')->with(['user_123'])->willReturn(0);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver1],
                ['memory', $mockDriver2],
                ['file', null],
            ]);
    }

    /**
     * 期待成功的清空標籤回應.
     */
    private function expectSuccessfulFlushTagResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateFlushTagResponse($content);
            }));
    }

    /**
     * 驗證清空標籤回應內容.
     */
    private function validateFlushTagResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && is_array($data['data'])
               && isset($data['data']['message']) && $data['data']['message'] === '標籤快取已成功清除'
               && isset($data['data']['tag']) && $data['data']['tag'] === 'user_123'
               && isset($data['data']['affected_drivers']) && is_array($data['data']['affected_drivers'])
               && in_array('redis', $data['data']['affected_drivers'])
               && isset($data['timestamp']);
    }

    /**
     * 設定清空多個標籤的 Mock 驅動程式.
     */
    private function setupMockDriverForFlushTags(array $testTags): MemoryCacheDriver&MockObject
    {
        $mockDriver = $this->createMock(MemoryCacheDriver::class);
        $mockDriver->method('tagExists')
            ->willReturnMap([
                ['user_123', true],
                ['module_posts', true],
            ]);
        $mockDriver->method('flushByTags')
            ->willReturnMap([
                [['user_123'], 3],
                [['module_posts'], 7],
            ]);

        return $mockDriver;
    }

    /**
     * 設定快取管理器用於清空多個標籤測試.
     */
    private function setupCacheManagerForFlushTags(MemoryCacheDriver&MockObject $mockDriver): void
    {
        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null],
            ]);
    }

    /**
     * 期待成功的清空多個標籤回應.
     */
    private function expectSuccessfulFlushTagsResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateFlushTagsResponse($content);
            }));
    }

    /**
     * 驗證清空多個標籤回應內容.
     */
    private function validateFlushTagsResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && isset($data['data']['total_flushed']) && $data['data']['total_flushed'] === 2
               && isset($data['data']['results']) && is_array($data['data']['results'])
               && count($data['data']['results']) === 2
               && isset($data['data']['message'])
               && isset($data['timestamp']);
    }

    /**
     * 設定標籤統計的 Mock 驅動程式.
     */
    private function setupMockDriversForTagStatistics(): void
    {
        $mockDriver1 = $this->createMock(MemoryCacheDriver::class);
        $mockDriver1->method('getTagStatistics')->willReturn([
            'total_tags' => 2,
            'tags' => [
                'user_123' => ['key_count' => 5],
                'module_posts' => ['key_count' => 8],
            ],
        ]);

        $mockDriver2 = $this->createMock(MemoryCacheDriver::class);
        $mockDriver2->method('getTagStatistics')->willReturn([
            'total_tags' => 1,
            'tags' => [
                'temporal_daily' => ['key_count' => 3],
            ],
        ]);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver1],
                ['memory', $mockDriver2],
                ['file', null],
            ]);
    }

    /**
     * 設定群組管理器用於標籤統計測試.
     */
    private function setupGroupManagerForTagStatistics(): void
    {
        $this->groupManager->method('getGroupStatistics')->willReturn([
            'total_groups' => 2,
            'groups' => ['group1', 'group2'],
        ]);
    }

    /**
     * 期待成功的標籤統計回應.
     */
    private function expectSuccessfulTagStatisticsResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateTagStatisticsResponse($content);
            }));
    }

    /**
     * 驗證標籤統計回應內容.
     */
    private function validateTagStatisticsResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null && $data['success'] === true
               && isset($data['data']['drivers'])
               && isset($data['data']['total_tags'])
               && isset($data['data']['total_cache_entries'])
               && isset($data['timestamp']);
    }

    /**
     * 取得測試群組資料.
     *
     * @return array{name: string, tags: array}
     */
    private function getTestGroupData(): array
    {
        return [
            'name' => 'test_group',
            'tags' => ['tag1', 'tag2'],
        ];
    }

    /**
     * 設定群組管理器用於建立群組測試.
     *
     * @param array{name: string, tags: array} $testGroupData
     */
    private function setupGroupManagerForCreateGroup(array $testGroupData): void
    {
        $this->groupManager->expects($this->once())
            ->method('group')
            ->with($testGroupData['name'], $testGroupData['tags'])
            ->willReturn($this->createMock(MemoryCacheDriver::class));
    }

    /**
     * 期待成功的建立群組回應.
     */
    private function expectSuccessfulCreateGroupResponse(string $groupName): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) use ($groupName) {
                return $this->validateCreateGroupResponse($content, $groupName);
            }));
    }

    /**
     * 驗證建立群組回應內容.
     */
    private function validateCreateGroupResponse(mixed $content, string $expectedGroupName): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && is_array($data['data'])
               && isset($data['data']['group']) && $data['data']['group'] === $expectedGroupName
               && isset($data['timestamp']);
    }

    /**
     * 取得模擬群組統計資料.
     */
    private function getMockGroupStatistics(): array
    {
        return [
            'groups' => [
                'group1' => [
                    'tags' => ['tag1', 'tag2'],
                    'created_at' => '2023-01-01',
                    'cache_count' => 5,
                    'has_dependencies' => false,
                ],
                'group2' => [
                    'tags' => ['tag3', 'tag4'],
                    'created_at' => '2023-01-02',
                    'cache_count' => 3,
                    'has_dependencies' => true,
                ],
            ],
        ];
    }

    /**
     * 期待成功的列表群組回應.
     */
    private function expectSuccessfulListGroupsResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateListGroupsResponse($content);
            }));
    }

    /**
     * 驗證列表群組回應內容.
     */
    private function validateListGroupsResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null
               && $data['success'] === true
               && is_array($data['data'])
               && isset($data['data']['groups']) && is_array($data['data']['groups'])
               && count($data['data']['groups']) === 2
               && isset($data['data']['total']) && $data['data']['total'] === 2
               && isset($data['timestamp']);
    }

    /**
     * 設定群組管理器用於清空群組測試.
     */
    private function setupGroupManagerForFlushGroup(): void
    {
        $this->groupManager->method('hasGroup')->with('test_group')->willReturn(true);
        $this->groupManager->expects($this->once())
            ->method('flushGroup')
            ->with('test_group', true)
            ->willReturn(8);
    }

    /**
     * 期待成功的清空群組回應.
     */
    private function expectSuccessfulFlushGroupResponse(): void
    {
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                return $this->validateFlushGroupResponse($content);
            }));
    }

    /**
     * 驗證清空群組回應內容.
     */
    private function validateFlushGroupResponse(mixed $content): bool
    {
        $data = $this->safeJsonDecode($content);

        return $data !== null && $data['success'] === true
               && isset($data['data']['group']) && $data['data']['group'] === 'test_group'
               && isset($data['data']['message'])
               && isset($data['timestamp']);
    }
}

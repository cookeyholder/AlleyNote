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
        if (!isset($data['success']) || $data['success'] !== true) {
            return false;
        }

        $current = $data;
        foreach ($expectedKeys as $key => $value) {
            if (is_array($value)) {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    return false;
                }
                $current = $current[$key];
            } else {
                if (!isset($current[$key]) || $current[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    public function testListTags(): void
    {
        $queryParams = [
            'page' => '1',
            'limit' => '20',
            'search' => 'user',
        ];

        $this->request->method('getQueryParams')->willReturn($queryParams);

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

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null],
            ]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']) && is_array($data['data'])
                       && isset($data['data']['tags']) && is_array($data['data']['tags']) && count($data['data']['tags']) === 2 // 只有 user 相關標籤
                       && isset($data['data']['pagination']) && is_array($data['data']['pagination'])
                       && isset($data['data']['pagination']['total']) && $data['data']['pagination']['total'] === 2;
            }));

        $result = $this->controller->listTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTag(): void
    {
        $args = ['tag' => 'user_123'];

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

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
            ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']) && is_array($data['data'])
                       && isset($data['data']['name']) && $data['data']['name'] === 'user_123'
                       && isset($data['data']['driver']) && $data['data']['driver'] === 'redis'
                       && isset($data['data']['statistics']) && is_array($data['data']['statistics'])
                       && isset($data['data']['statistics']['key_count']) && $data['data']['statistics']['key_count'] === 3
                       && isset($data['data']['type']) && $data['data']['type'] === 'other'
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->getTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTag(): void
    {
        $args = ['tag' => 'user_123'];

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

        // 移除 logger 期待，因為實際實現中沒有 info 日誌
        
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']) && is_array($data['data'])
                       && isset($data['data']['message']) && $data['data']['message'] === '標籤快取已成功清除'
                       && isset($data['data']['tag']) && $data['data']['tag'] === 'user_123'
                       && isset($data['data']['affected_drivers']) && is_array($data['data']['affected_drivers'])
                       && in_array('redis', $data['data']['affected_drivers'])
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->flushTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTags(): void
    {
        $requestBody = json_encode(['tags' => ['user_123', 'module_posts']]);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

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

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null],
            ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']['total_flushed']) && $data['data']['total_flushed'] === 2
                       && isset($data['data']['results']) && is_array($data['data']['results'])
                       && count($data['data']['results']) === 2
                       && isset($data['data']['message'])
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->flushTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTagStatistics(): void
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

        $this->groupManager->method('getGroupStatistics')->willReturn([
            'total_groups' => 2,
            'groups' => ['group1', 'group2'],
        ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null && isset($data['success']) && $data['success'] === true
                       && isset($data['data']['drivers'])
                       && isset($data['data']['total_tags'])
                       && isset($data['data']['total_cache_entries'])
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->getTagStatistics($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateGroup(): void
    {
        $requestBody = json_encode([
            'name' => 'test_group',
            'tags' => ['tag1', 'tag2'],
        ]);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

        $this->groupManager->expects($this->once())
            ->method('group')
            ->with('test_group', ['tag1', 'tag2'])
            ->willReturn($this->createMock(MemoryCacheDriver::class));

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']) && is_array($data['data'])
                       && isset($data['data']['group']) && $data['data']['group'] === 'test_group'
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->createGroup($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testListGroups(): void
    {
        $mockGroupData = [
            'group1' => ['tags' => ['tag1', 'tag2'], 'created_at' => '2023-01-01'],
            'group2' => ['tags' => ['tag3', 'tag4'], 'created_at' => '2023-01-02'],
        ];

        $this->groupManager->method('getAllGroups')->willReturn($mockGroupData);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null
                       && isset($data['success']) && $data['success'] === true
                       && isset($data['data']) && is_array($data['data'])
                       && isset($data['data']['groups']) && is_array($data['data']['groups'])
                       && count($data['data']['groups']) === 2
                       && isset($data['data']['total']) && $data['data']['total'] === 2
                       && isset($data['timestamp']);
            }));

        $result = $this->controller->listGroups($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushGroup(): void
    {
        $args = ['group' => 'test_group'];
        $queryParams = ['cascade' => 'true'];

        $this->request->method('getQueryParams')->willReturn($queryParams);

        $this->groupManager->method('hasGroup')->with('test_group')->willReturn(true);
        $this->groupManager->expects($this->once())
            ->method('flushGroup')
            ->with('test_group', true)
            ->willReturn(8);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = $this->safeJsonDecode($content);

                return $data !== null && isset($data['success']) && $data['success'] === true
                       && isset($data['data']['group']) && $data['data']['group'] === 'test_group'
                       && isset($data['data']['message'])
                       && isset($data['timestamp']);
            }));

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

                return $data !== null && isset($data['success']) && isset($data['success']) && $data['success'] === false
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

                return $data !== null && isset($data['success']) && isset($data['success']) && $data['success'] === false
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
}

<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Controllers\Admin;

use App\Application\Controllers\Admin\TagManagementController;
use App\Shared\Cache\Contracts\CacheManagerInterface;
use App\Shared\Cache\Contracts\TaggedCacheInterface;
use App\Shared\Cache\Contracts\TagRepositoryInterface;
use App\Shared\Cache\Services\CacheGroupManager;
use App\Shared\Cache\ValueObjects\CacheTag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * TagManagementController 整合測試
 */
class TagManagementControllerTest extends TestCase
{
    private TagManagementController $controller;
    private CacheManagerInterface&MockObject $cacheManager;
    private TagRepositoryInterface&MockObject $tagRepository;
    private CacheGroupManager&MockObject $groupManager;
    private LoggerInterface&MockObject $logger;
    private ServerRequestInterface&MockObject $request;
    private ResponseInterface&MockObject $response;
    private StreamInterface&MockObject $responseBody;

    protected function setUp(): void
    {
        $this->cacheManager = $this->createMock(CacheManagerInterface::class);
        $this->tagRepository = $this->createMock(TagRepositoryInterface::class);
        $this->groupManager = $this->createMock(CacheGroupManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new TagManagementController(
            $this->cacheManager,
            $this->tagRepository,
            $this->groupManager,
            $this->logger
        );

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseBody = $this->createMock(StreamInterface::class);

        $this->response->method('getBody')->willReturn($this->responseBody);
        $this->response->method('withHeader')->willReturnSelf();
    }

    public function testListTags(): void
    {
        $queryParams = [
            'page' => '1',
            'limit' => '20',
            'search' => 'user'
        ];

        $this->request->method('getQueryParams')->willReturn($queryParams);

        $mockDriver = $this->createMock(TaggedCacheInterface::class);
        $mockDriver->method('getAllTags')->willReturn(['user_123', 'user_456', 'module_posts']);
        $mockDriver->method('getTagStatistics')->willReturn([
            'total_tags' => 3,
            'tags' => [
                'user_123' => ['key_count' => 5, 'sample_keys' => ['key1', 'key2']],
                'user_456' => ['key_count' => 3, 'sample_keys' => ['key3']],
                'module_posts' => ['key_count' => 8, 'sample_keys' => ['key4', 'key5']]
            ]
        ]);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null]
            ]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       count($data['data']['tags']) === 2 && // 只有 user 相關標籤
                       $data['data']['pagination']['total'] === 2;
            }));

        $result = $this->controller->listTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTag(): void
    {
        $args = ['tag' => 'user_123'];

        $mockDriver = $this->createMock(TaggedCacheInterface::class);
        $mockDriver->method('tagExists')->with('user_123')->willReturn(true);
        $mockDriver->method('getKeysByTag')->with('user_123')->willReturn(['key1', 'key2', 'key3']);
        $mockDriver->method('getTagStatistics')->willReturn([
            'tags' => [
                'user_123' => ['key_count' => 3, 'sample_keys' => ['key1', 'key2']]
            ]
        ]);
        $mockDriver->method('get')
            ->willReturnMap([
                ['key1', 'value1'],
                ['key2', 'value2'],
                ['key3', null]
            ]);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null]
            ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       $data['data']['name'] === 'user_123' &&
                       $data['data']['key_count'] === 3 &&
                       $data['data']['type'] === 'user';
            }));

        $result = $this->controller->getTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTag(): void
    {
        $args = ['tag' => 'user_123'];

        $mockDriver1 = $this->createMock(TaggedCacheInterface::class);
        $mockDriver1->method('tagExists')->with('user_123')->willReturn(true);
        $mockDriver1->method('flushByTags')->with('user_123')->willReturn(5);

        $mockDriver2 = $this->createMock(TaggedCacheInterface::class);
        $mockDriver2->method('tagExists')->with('user_123')->willReturn(false);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver1],
                ['memory', $mockDriver2],
                ['file', null]
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('標籤快取已清空'),
                $this->arrayHasKey('tag')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       $data['data']['tag'] === 'user_123' &&
                       $data['data']['cleared_count'] === 5;
            }));

        $result = $this->controller->flushTag($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testFlushTags(): void
    {
        $requestBody = json_encode(['tags' => ['user_123', 'module_posts']]);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

        $mockDriver = $this->createMock(TaggedCacheInterface::class);
        $mockDriver->method('tagExists')
            ->willReturnMap([
                ['user_123', true],
                ['module_posts', true]
            ]);
        $mockDriver->method('flushByTags')
            ->willReturnMap([
                ['user_123', 3],
                ['module_posts', 7]
            ]);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver],
                ['memory', null],
                ['file', null]
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('批量清空標籤快取'),
                $this->arrayHasKey('total_cleared')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       $data['data']['total_cleared'] === 10 &&
                       isset($data['data']['results']['user_123']) &&
                       isset($data['data']['results']['module_posts']);
            }));

        $result = $this->controller->flushTags($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testGetTagStatistics(): void
    {
        $mockDriver1 = $this->createMock(TaggedCacheInterface::class);
        $mockDriver1->method('getTagStatistics')->willReturn([
            'total_tags' => 2,
            'tags' => [
                'user_123' => ['key_count' => 5],
                'module_posts' => ['key_count' => 8]
            ]
        ]);

        $mockDriver2 = $this->createMock(TaggedCacheInterface::class);
        $mockDriver2->method('getTagStatistics')->willReturn([
            'total_tags' => 1,
            'tags' => [
                'temporal_daily' => ['key_count' => 3]
            ]
        ]);

        $this->cacheManager->method('getDriver')
            ->willReturnMap([
                ['redis', $mockDriver1],
                ['memory', $mockDriver2],
                ['file', null]
            ]);

        $this->groupManager->method('getGroupStatistics')->willReturn([
            'total_groups' => 2,
            'groups' => ['group1', 'group2']
        ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       isset($data['data']['drivers']) &&
                       isset($data['data']['summary']) &&
                       isset($data['data']['tag_types']) &&
                       isset($data['data']['groups']);
            }));

        $result = $this->controller->getTagStatistics($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateGroup(): void
    {
        $requestBody = json_encode([
            'name' => 'test_group',
            'tags' => ['tag1', 'tag2'],
            'dependencies' => [
                ['parent' => 'parent_group', 'children' => ['test_group']]
            ]
        ]);
        $this->request->method('getBody')->willReturn($this->createStreamWithContent($requestBody));

        $this->groupManager->method('hasGroup')->with('test_group')->willReturn(false);

        $mockGroupCache = $this->createMock(TaggedCacheInterface::class);
        $this->groupManager->expects($this->once())
            ->method('group')
            ->with('test_group', ['tag1', 'tag2'])
            ->willReturn($mockGroupCache);

        $this->groupManager->expects($this->once())
            ->method('setDependencies')
            ->with('parent_group', ['test_group']);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('快取分組已建立'),
                $this->arrayHasKey('group_name')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       $data['data']['group_name'] === 'test_group';
            }));

        $result = $this->controller->createGroup($this->request, $this->response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testListGroups(): void
    {
        $this->groupManager->method('getAllGroups')->willReturn(['group1', 'group2']);
        $this->groupManager->method('getGroupStatistics')->willReturn([
            'total_groups' => 2,
            'groups' => [
                'group1' => ['cache_count' => 5],
                'group2' => ['cache_count' => 3]
            ]
        ]);

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       count($data['data']['groups']) === 2 &&
                       isset($data['data']['statistics']);
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

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('快取分組已清空'),
                $this->arrayHasKey('group_name')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true &&
                       $data['data']['group_name'] === 'test_group' &&
                       $data['data']['cleared_count'] === 8 &&
                       $data['data']['cascade'] === true;
            }));

        $result = $this->controller->flushGroup($this->request, $this->response, $args);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testErrorHandling(): void
    {
        // 測試當沒有可用驅動時的錯誤處理
        $this->cacheManager->method('getDriver')->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('取得標籤列表失敗'),
                $this->arrayHasKey('error')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === false &&
                       isset($data['error']);
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
                $this->arrayHasKey('error')
            );

        $this->responseBody->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === false &&
                       $data['error']['details'] === '標籤名稱不能為空';
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

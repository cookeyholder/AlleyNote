<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Http;

use App\Domains\Auth\Exceptions\AuthenticationException;
use App\Domains\Auth\Exceptions\ForbiddenException;
use App\Domains\Auth\Exceptions\InvalidTokenException;
use App\Domains\Auth\Exceptions\TokenExpiredException;
use App\Domains\Auth\Exceptions\UnauthorizedException;
use App\Domains\Post\Exceptions\PostNotFoundException;
use App\Domains\Post\Exceptions\PostStatusException;
use App\Infrastructure\Http\ExceptionRegistry;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Http\ServerRequest;
use App\Infrastructure\Http\ServerRequestFactory;
use App\Infrastructure\Http\Stream;
use App\Infrastructure\Http\Uri;
use App\Shared\Enums\HttpStatusCode;
use App\Shared\Exceptions\ApiExceptionInterface;
use App\Shared\Exceptions\CsrfTokenException;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\Validation\RequestValidationException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use Exception;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Tests\Support\UnitTestCase;
use Throwable;

/**
 * HTTP 基礎設施單元測試 (Uri, Stream, Response, ServerRequest, ServerRequestFactory, ExceptionRegistry).
 */
class HttpTest extends UnitTestCase
{
    /**
     * 測試 Uri 實作與各項方法.
     */
    public function testUri(): void
    {
        $uri = new Uri('https://user:pass@example.com:8080/posts/1?query=php#heading');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals(8080, $uri->getPort());
        $this->assertEquals('user:pass', $uri->getUserInfo());
        $this->assertEquals('user:pass@example.com:8080', $uri->getAuthority());
        $this->assertEquals('/posts/1', $uri->getPath());
        $this->assertEquals('query=php', $uri->getQuery());
        $this->assertEquals('heading', $uri->getFragment());
        $this->assertEquals('https://user:pass@example.com:8080/posts/1?query=php#heading', (string) $uri);

        // 測試預設端口忽略
        $httpDefaultUri = new Uri('http://example.com:80/path');
        $this->assertEquals('example.com', $httpDefaultUri->getAuthority());
        $this->assertEquals('http://example.com/path', (string) $httpDefaultUri);

        $httpsDefaultUri = new Uri('https://example.com:443/path');
        $this->assertEquals('example.com', $httpsDefaultUri->getAuthority());
        $this->assertEquals('https://example.com/path', (string) $httpsDefaultUri);

        // 測試不可變更改方法
        $newUri = new Uri()
            ->withScheme('HTTP')
            ->withHost('LOCALHOST')
            ->withPort(3000)
            ->withUserInfo('admin', 'secret')
            ->withPath('/api/v1')
            ->withQuery('filter=active')
            ->withFragment('top');

        $this->assertEquals('http', $newUri->getScheme());
        $this->assertEquals('localhost', $newUri->getHost());
        $this->assertEquals(3000, $newUri->getPort());
        $this->assertEquals('admin:secret', $newUri->getUserInfo());
        $this->assertEquals('admin:secret@localhost:3000', $newUri->getAuthority());
        $this->assertEquals('/api/v1', $newUri->getPath());
        $this->assertEquals('filter=active', $newUri->getQuery());
        $this->assertEquals('top', $newUri->getFragment());
        $this->assertEquals('http://admin:secret@localhost:3000/api/v1?filter=active#top', (string) $newUri);

        // 測試 withUserInfo 僅傳入 user
        $userOnlyUri = new Uri()->withUserInfo('singleuser');
        $this->assertEquals('singleuser', $userOnlyUri->getUserInfo());
    }

    /**
     * 測試 Stream 實作與各項方法.
     */
    public function testStream(): void
    {
        $stream = new Stream('Initial Content');

        $this->assertEquals('Initial Content', (string) $stream);
        $this->assertEquals(15, $stream->getSize());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());

        // 測試 seek, tell, rewind, read
        $stream->seek(8);
        $this->assertEquals(8, $stream->tell());
        $this->assertEquals('Content', $stream->read(7));
        $stream->read(1); // 嘗試讀取超過結尾觸發 EOF
        $this->assertTrue($stream->eof());

        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
        $this->assertEquals('Initial Content', $stream->getContents());

        // 測試 write
        $stream->seek(0);
        $written = $stream->write('Updated');
        $this->assertEquals(7, $written);
        $stream->rewind();
        $this->assertEquals('Updated Content', $stream->getContents());

        // 測試 metadata
        $this->assertIsArray($stream->getMetadata());
        $this->assertEquals('php://temp', $stream->getMetadata('uri'));
        $this->assertNull($stream->getMetadata('nonexistent_key'));

        // 測試以 resource 建立
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'Resource test');
        rewind($resource);
        $resourceStream = new Stream($resource);
        $this->assertEquals('Resource test', (string) $resourceStream);

        // 測試 detach 與 close
        $detachedResource = $resourceStream->detach();
        $this->assertIsResource($detachedResource);
        fclose($detachedResource);
        $this->assertFalse($resourceStream->isReadable());
        $this->assertFalse($resourceStream->isWritable());
        $this->assertFalse($resourceStream->isSeekable());
        $this->assertNull($resourceStream->getSize());
        $this->assertNull($resourceStream->getMetadata('uri'));
        $this->assertTrue($resourceStream->eof());

        $stream->close();

        // 測試無效建構參數
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Body must be a string or resource');
        new Stream(['invalid_array']);
    }

    /**
     * 測試 Stream 關閉後調用操作拋出例外.
     */
    public function testStreamDetachedExceptions(): void
    {
        $stream = new Stream('test');
        $stream->detach();

        try {
            $stream->tell();
            $this->fail('Expected RuntimeException for tell');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stream is detached', $e->getMessage());
        }

        try {
            $stream->seek(0);
            $this->fail('Expected RuntimeException for seek');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stream is detached', $e->getMessage());
        }

        try {
            $stream->write('data');
            $this->fail('Expected RuntimeException for write');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stream is detached', $e->getMessage());
        }

        try {
            $stream->read(10);
            $this->fail('Expected RuntimeException for read');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stream is detached', $e->getMessage());
        }

        try {
            $stream->getContents();
            $this->fail('Expected RuntimeException for getContents');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stream is detached', $e->getMessage());
        }
    }

    /**
     * 測試 Response 實作與標頭管理.
     */
    public function testResponse(): void
    {
        $response = new Response(
            statusCode: 201,
            headers: ['Content-Type' => 'application/json', 'X-Custom' => ['one', 'two']],
            body: '{"created":true}',
            protocolVersion: '1.1',
            reasonPhrase: 'Custom Created',
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Custom Created', $response->getReasonPhrase());
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals('{"created":true}', (string) $response->getBody());

        // 測試預設原因短語
        $defaultResponse = new Response(404);
        $this->assertEquals('Not Found', $defaultResponse->getReasonPhrase());

        $unknownCodeResponse = new Response(499);
        $this->assertEquals('', $unknownCodeResponse->getReasonPhrase());

        // 測試標頭操作
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertFalse($response->hasHeader('Authorization'));

        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertEquals(['one', 'two'], $response->getHeader('x-custom'));
        $this->assertEquals('one, two', $response->getHeaderLine('X-Custom'));
        $this->assertEmpty($response->getHeader('unknown'));

        // 測試不可變標頭修改
        $resp2 = $response->withHeader('X-New', 'new-val');
        $this->assertFalse($response->hasHeader('X-New'));
        $this->assertTrue($resp2->hasHeader('X-New'));

        $resp3 = $resp2->withAddedHeader('X-New', 'second-val');
        $this->assertEquals(['new-val', 'second-val'], $resp3->getHeader('X-New'));

        $resp4 = $resp3->withAddedHeader('X-Brand-New', 'brand-val');
        $this->assertEquals(['brand-val'], $resp4->getHeader('X-Brand-New'));

        $resp5 = $resp4->withoutHeader('X-New');
        $this->assertFalse($resp5->hasHeader('X-New'));
        $this->assertTrue($resp4->hasHeader('X-New'));

        // withoutHeader 移除非現有標頭
        $this->assertSame($resp5->getHeaders(), $resp5->withoutHeader('nonexistent')->getHeaders());

        // 測試 withStatus, withProtocolVersion, withBody
        $resp6 = $resp5->withStatus(204, 'No Content Body')
            ->withProtocolVersion('2.0')
            ->withBody(new Stream('new stream'));

        $this->assertEquals(204, $resp6->getStatusCode());
        $this->assertEquals('No Content Body', $resp6->getReasonPhrase());
        $this->assertEquals('2.0', $resp6->getProtocolVersion());
        $this->assertEquals('new stream', (string) $resp6->getBody());
    }

    /**
     * 測試 ServerRequest 實作與屬性、參數管理.
     */
    public function testServerRequest(): void
    {
        $uri = new Uri('http://localhost:8080/api/posts?filter=all');
        $request = new ServerRequest(
            method: 'POST',
            uri: $uri,
            headers: ['Content-Type' => 'application/json', 'X-Key' => 'secret'],
            body: new Stream('{"key":"value"}'),
            version: '1.1',
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
        );

        $this->assertEquals('POST', $request->getMethod());
        $this->assertSame($uri, $request->getUri());
        $this->assertEquals('/api/posts', $request->getRequestTarget());
        $this->assertEquals(['REMOTE_ADDR' => '127.0.0.1'], $request->getServerParams());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('{"key":"value"}', (string) $request->getBody());
        $this->assertEmpty($request->getUploadedFiles());

        // 測試 withRequestTarget
        $reqTarget = $request->withRequestTarget('/custom/target');
        $this->assertEquals('/custom/target', $reqTarget->getRequestTarget());

        // 測試 withMethod
        $reqGet = $request->withMethod('GET');
        $this->assertEquals('GET', $reqGet->getMethod());

        // 測試 withUri
        $newUri = new Uri('http://localhost/new');
        $reqNewUri = $request->withUri($newUri);
        $this->assertSame($newUri, $reqNewUri->getUri());

        // 測試 cookieParams
        $reqCookies = $request->withCookieParams(['session_id' => 'abc']);
        $this->assertEquals(['session_id' => 'abc'], $reqCookies->getCookieParams());

        // 測試 queryParams
        $reqQuery = $request->withQueryParams(['page' => '1']);
        $this->assertEquals(['page' => '1'], $reqQuery->getQueryParams());

        // 測試 parsedBody
        $reqBody = $request->withParsedBody(['name' => 'Alley']);
        $this->assertEquals(['name' => 'Alley'], $reqBody->getParsedBody());

        // 測試 attributes
        $this->assertEmpty($request->getAttributes());
        $this->assertNull($request->getAttribute('route_info'));
        $this->assertEquals('default', $request->getAttribute('unknown', 'default'));

        $reqAttr = $request->withAttribute('user_id', 42);
        $this->assertEquals(42, $reqAttr->getAttribute('user_id'));
        $this->assertEquals(['user_id' => 42], $reqAttr->getAttributes());

        $reqNoAttr = $reqAttr->withoutAttribute('user_id');
        $this->assertNull($reqNoAttr->getAttribute('user_id'));

        // 測試 headers 操作
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));

        $reqHeader = $request->withHeader('X-Custom', 'val1');
        $this->assertEquals(['val1'], $reqHeader->getHeader('X-Custom'));

        $reqHeader2 = $reqHeader->withAddedHeader('X-Custom', 'val2');
        $this->assertEquals(['val1', 'val2'], $reqHeader2->getHeader('X-Custom'));

        $reqHeader3 = $reqHeader2->withoutHeader('X-Custom');
        $this->assertFalse($reqHeader3->hasHeader('X-Custom'));

        // 測試 withProtocolVersion & withBody & withUploadedFiles
        $reqVersion = $request->withProtocolVersion('2.0')
            ->withBody(new Stream('new body'))
            ->withUploadedFiles([]);
        $this->assertEquals('2.0', $reqVersion->getProtocolVersion());
        $this->assertEquals('new body', (string) $reqVersion->getBody());
    }

    /**
     * 測試 ServerRequestFactory::fromGlobals.
     */
    public function testServerRequestFactoryFromGlobals(): void
    {
        $backupServer = $_SERVER;
        $backupGet = $_GET;
        $backupPost = $_POST;
        $backupCookie = $_COOKIE;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '8443';
        $_SERVER['REQUEST_URI'] = '/api/posts?active=1';
        $_SERVER['QUERY_STRING'] = 'active=1';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['CONTENT_LENGTH'] = '100';

        $_GET = ['active' => '1'];
        $_POST = ['title' => 'Test Post'];
        $_COOKIE = ['session' => 'cookie_val'];

        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertEquals('example.com', $request->getUri()->getHost());
        $this->assertEquals(8443, $request->getUri()->getPort());
        $this->assertEquals('/api/posts', $request->getUri()->getPath());
        $this->assertEquals(['active' => '1'], $request->getQueryParams());
        $this->assertEquals(['session' => 'cookie_val'], $request->getCookieParams());
        $this->assertEquals(['title' => 'Test Post'], $request->getParsedBody());
        $this->assertEquals(['Bearer token123'], $request->getHeader('authorization'));
        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
        $this->assertEquals(['100'], $request->getHeader('content-length'));

        // 復原超全域變數
        $_SERVER = $backupServer;
        $_GET = $backupGet;
        $_POST = $backupPost;
        $_COOKIE = $backupCookie;
    }

    /**
     * 測試 ExceptionRegistry.
     */
    public function testExceptionRegistry(): void
    {
        $registry = ExceptionRegistry::createDefault();

        // 測試預設映射
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $registry->resolve(new PostNotFoundException(1)));
        $this->assertEquals(HttpStatusCode::UNPROCESSABLE_ENTITY, $registry->resolve(new PostStatusException('invalid status')));
        $this->assertEquals(HttpStatusCode::NOT_FOUND, $registry->resolve(new NotFoundException()));
        $this->assertEquals(HttpStatusCode::UNPROCESSABLE_ENTITY, $registry->resolve(new StateTransitionException()));
        $this->assertEquals(HttpStatusCode::UNPROCESSABLE_ENTITY, $registry->resolve(new ValidationException(new ValidationResult(false))));
        $this->assertEquals(HttpStatusCode::UNPROCESSABLE_ENTITY, $registry->resolve(new RequestValidationException('error')));
        $this->assertEquals(HttpStatusCode::UNAUTHORIZED, $registry->resolve(new UnauthorizedException()));
        $this->assertEquals(HttpStatusCode::FORBIDDEN, $registry->resolve(new ForbiddenException()));
        $this->assertEquals(HttpStatusCode::UNAUTHORIZED, $registry->resolve(new TokenExpiredException()));
        $this->assertEquals(HttpStatusCode::UNAUTHORIZED, $registry->resolve(new InvalidTokenException()));
        $this->assertEquals(HttpStatusCode::UNAUTHORIZED, $registry->resolve(new AuthenticationException()));
        $this->assertEquals(HttpStatusCode::FORBIDDEN, $registry->resolve(new CsrfTokenException()));

        // 測試 ApiExceptionInterface 實作
        $apiExceptionMock = Mockery::mock(Exception::class, ApiExceptionInterface::class);
        $apiExceptionMock->shouldReceive('getHttpStatusCode')->andReturn(HttpStatusCode::BAD_REQUEST);
        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $registry->resolve($apiExceptionMock));

        // 測試 ApiExceptionInterface 回傳整數狀態碼
        $apiExceptionIntMock = Mockery::mock(Exception::class, ApiExceptionInterface::class);
        $apiExceptionIntMock->shouldReceive('getHttpStatusCode')->andReturn(500);
        $this->assertEquals(HttpStatusCode::INTERNAL_SERVER_ERROR, $registry->resolve($apiExceptionIntMock));

        // 測試自訂類別與介面註冊
        $customRegistry = new ExceptionRegistry();
        $customRegistry->register(InvalidArgumentException::class, HttpStatusCode::BAD_REQUEST);
        $this->assertEquals(HttpStatusCode::BAD_REQUEST, $customRegistry->resolve(new InvalidArgumentException()));

        $customRegistry->registerInterface(Throwable::class, HttpStatusCode::INTERNAL_SERVER_ERROR);
        $this->assertEquals(HttpStatusCode::INTERNAL_SERVER_ERROR, $customRegistry->resolve(new Exception()));

        // 測試未註冊的例外
        $emptyRegistry = new ExceptionRegistry();
        $this->assertNull($emptyRegistry->resolve(new Exception()));
    }
}

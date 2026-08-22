<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Repositories;

use App\Domains\Auth\Repositories\UserRepository;
use App\Domains\Auth\Repositories\UserRepositoryAdapter;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * 使用者儲存庫轉接器單元測試.
 */
final class UserRepositoryAdapterTest extends UnitTestCase
{
    private UserRepository&MockInterface $userRepository;

    private UserRepositoryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->adapter = new UserRepositoryAdapter($this->userRepository);
    }

    /**
     * 測試 findByUsername.
     */
    public function testFindByUsername(): void
    {
        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('alice')
            ->andReturn(['id' => 1, 'username' => 'alice']);

        $result = $this->adapter->findByUsername('alice');
        $this->assertSame(['id' => 1, 'username' => 'alice'], $result);
    }

    /**
     * 測試 findByEmail.
     */
    public function testFindByEmail(): void
    {
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('alice@example.com')
            ->andReturn(['id' => 1, 'email' => 'alice@example.com']);

        $result = $this->adapter->findByEmail('alice@example.com');
        $this->assertSame(['id' => 1, 'email' => 'alice@example.com'], $result);
    }

    /**
     * 測試 findByUuid.
     */
    public function testFindByUuid(): void
    {
        $this->userRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->with('uuid-123')
            ->andReturn(['id' => 1, 'uuid' => 'uuid-123']);

        $result = $this->adapter->findByUuid('uuid-123');
        $this->assertSame(['id' => 1, 'uuid' => 'uuid-123'], $result);
    }

    /**
     * 測試 validateCredentials 成功 (透過 Email 與 password_hash).
     */
    public function testValidateCredentialsSuccessByEmail(): void
    {
        $hash = password_hash('SecretPass123!', PASSWORD_BCRYPT);
        $user = ['id' => 1, 'email' => 'user@example.com', 'password_hash' => $hash];

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com')
            ->andReturn($user);

        $result = $this->adapter->validateCredentials('user@example.com', 'SecretPass123!');
        $this->assertSame($user, $result);
    }

    /**
     * 測試 validateCredentials 成功 (透過 Username 與 password 欄位).
     */
    public function testValidateCredentialsSuccessByUsername(): void
    {
        $hash = password_hash('SecretPass123!', PASSWORD_BCRYPT);
        $user = ['id' => 2, 'username' => 'bob', 'password' => $hash];

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('bob')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('bob')
            ->andReturn($user);

        $result = $this->adapter->validateCredentials('bob', 'SecretPass123!');
        $this->assertSame($user, $result);
    }

    /**
     * 測試 validateCredentials 密碼錯誤或使用者不存在返回 null.
     */
    public function testValidateCredentialsFailures(): void
    {
        // 1. 使用者不存在
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('nonexistent')
            ->andReturn(null);
        $this->userRepository
            ->shouldReceive('findByUsername')
            ->once()
            ->with('nonexistent')
            ->andReturn(null);

        $this->assertNull($this->adapter->validateCredentials('nonexistent', 'pass'));

        // 2. 密碼欄位不存在
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('user_no_pass')
            ->andReturn(['id' => 3]);

        $this->assertNull($this->adapter->validateCredentials('user_no_pass', 'pass'));

        // 3. 密碼驗證不符
        $hash = password_hash('CorrectPass123!', PASSWORD_BCRYPT);
        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with('user_wrong_pass')
            ->andReturn(['id' => 4, 'password_hash' => $hash]);

        $this->assertNull($this->adapter->validateCredentials('user_wrong_pass', 'WrongPass'));
    }

    /**
     * 測試 updateLastLogin.
     */
    public function testUpdateLastLogin(): void
    {
        $this->userRepository
            ->shouldReceive('updateLastLogin')
            ->once()
            ->with('10')
            ->andReturn(true);

        $this->assertTrue($this->adapter->updateLastLogin(10));
    }

    /**
     * 測試 findById 與 findByIdWithRoles.
     */
    public function testFindByIdAndFindByIdWithRoles(): void
    {
        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1]);

        $this->userRepository
            ->shouldReceive('findByIdWithRoles')
            ->once()
            ->with(1)
            ->andReturn(['id' => 1, 'roles' => []]);

        $this->assertSame(['id' => 1], $this->adapter->findById(1));
        $this->assertSame(['id' => 1, 'roles' => []], $this->adapter->findByIdWithRoles(1));
    }

    /**
     * 測試 create.
     */
    public function testCreate(): void
    {
        $data = ['username' => 'test', 'email' => 'test@example.com'];
        $created = ['id' => 5, ...$data];

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($created);

        $this->assertSame($created, $this->adapter->create($data));
    }

    /**
     * 測試其餘預設實作方法.
     */
    public function testDefaultStubMethods(): void
    {
        $this->assertTrue($this->adapter->update(1, []));
        $this->assertTrue($this->adapter->delete(1));
        $this->assertFalse($this->adapter->usernameExists('name'));
        $this->assertFalse($this->adapter->emailExists('email@example.com'));
        $this->assertTrue($this->adapter->forceDelete(1));
        $this->assertTrue($this->adapter->restore(1));
        $this->assertSame([], $this->adapter->paginate());
        $this->assertSame([], $this->adapter->getTrashed());
        $this->assertSame([], $this->adapter->search('kw'));
        $this->assertSame([], $this->adapter->getStats());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * BaseDTO 測試類.
 */
class BaseDTOTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public mixed $name;

    public mixed $age;

    public mixed $active;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = Mockery::mock(ValidatorInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試用的具體 DTO 實作.
     */
    private function createTestDTO(): TestableBaseDTO
    {
        return new TestableBaseDTO($this->validator);
    }

    public function testConstructorAcceptsValidator(): void
    {
        $dto = $this->createTestDTO();
        $this->assertInstanceOf(BaseDTO::class, $dto);
    }

    public function testToArrayIsImplemented(): void
    {
        $dto = $this->createTestDTO();
        $result = $dto->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayHasKey('active', $result);
    }

    public function testJsonSerializeUsesToArray(): void
    {
        $dto = $this->createTestDTO();
        $dto->name = 'Test User';
        $dto->age = 25;
        $dto->active = true;

        $expected = [
            'name' => 'Test User',
            'age' => 25,
            'active' => true,
        ];

        $this->assertEquals($expected, $dto->jsonSerialize());
        $this->assertEquals(json_encode($expected), json_encode($dto));
    }

    public function testValidateCallsValidatorWithCorrectData(): void
    {
        $dto = $this->createTestDTO();
        $data = ['name' => 'John', 'age' => 30, 'active' => true];
        $expectedRules = [
            'name' => 'required|string|min_length:2|max_length:50',
            'age' => 'required|integer|min:0|max:120',
            'active' => 'boolean',
        ];

        $this->validator
            ->shouldReceive('validateOrFail')
            ->once()
            ->with($data, $expectedRules)
            ->andReturn($data);

        $result = $dto->testValidate($data);
        $this->assertEquals($data, $result);
    }

    public function testValidateThrowsExceptionOnValidationFailure(): void
    {
        $dto = $this->createTestDTO();
        $data = ['name' => '', 'age' => -1];

        /** @var ValidationException::class|MockInterface */
        /** @var mixed */
        $exception = Mockery::mock(ValidationException::class);

        $this->validator
            ->shouldReceive('validateOrFail')
            ->once()
            ->andThrow($exception);

        $this->expectException(ValidationException::class);
        $dto->testValidate($data);
    }

    public function testGetStringReturnsCorrectValue(): void
    {
        $dto = $this->createTestDTO();
        $data = [
            'name' => 'John Doe',
            'empty' => '',
            'spaces' => '  test  ',
            'null_value' => null,
        ];

        $this->assertEquals('John Doe', $dto->testGetString($data, 'name'));
        $this->assertEquals('', $dto->testGetString($data, 'empty'));
        $this->assertEquals('test', $dto->testGetString($data, 'spaces'));
        $this->assertNull($dto->testGetString($data, 'null_value'));
        $this->assertEquals('default', $dto->testGetString($data, 'missing', 'default'));
        $this->assertNull($dto->testGetString($data, 'missing'));
    }

    public function testGetIntReturnsCorrectValue(): void
    {
        $dto = $this->createTestDTO();
        $data = [
            'age' => 25,
            'string_number' => '30',
            'float_number' => 35.7,
            'null_value' => null,
        ];

        $this->assertEquals(25, $dto->testGetInt($data, 'age'));
        $this->assertEquals(30, $dto->testGetInt($data, 'string_number'));
        $this->assertEquals(35, $dto->testGetInt($data, 'float_number'));
        $this->assertNull($dto->testGetInt($data, 'null_value'));
        $this->assertEquals(100, $dto->testGetInt($data, 'missing', 100));
        $this->assertNull($dto->testGetInt($data, 'missing'));
    }

    public function testGetBoolReturnsCorrectValue(): void
    {
        $dto = $this->createTestDTO();
        $data = [
            'bool_true' => true,
            'bool_false' => false,
            'int_1' => 1,
            'int_0' => 0,
            'string_1' => '1',
            'string_0' => '0',
            'string_true' => 'true',
            'string_false' => 'false',
            'string_on' => 'on',
            'string_yes' => 'yes',
            'string_other' => 'other',
            'null_value' => null,
        ];

        $this->assertTrue($dto->testGetBool($data, 'bool_true'));
        $this->assertFalse($dto->testGetBool($data, 'bool_false'));
        $this->assertTrue($dto->testGetBool($data, 'int_1'));
        $this->assertFalse($dto->testGetBool($data, 'int_0'));
        $this->assertTrue($dto->testGetBool($data, 'string_1'));
        $this->assertFalse($dto->testGetBool($data, 'string_0'));
        $this->assertTrue($dto->testGetBool($data, 'string_true'));
        $this->assertFalse($dto->testGetBool($data, 'string_false'));
        $this->assertTrue($dto->testGetBool($data, 'string_on'));
        $this->assertTrue($dto->testGetBool($data, 'string_yes'));
        $this->assertFalse($dto->testGetBool($data, 'string_other'));
        $this->assertNull($dto->testGetBool($data, 'null_value'));
        $this->assertTrue($dto->testGetBool($data, 'missing', true));
        $this->assertNull($dto->testGetBool($data, 'missing'));
    }

    public function testGetValueReturnsCorrectValue(): void
    {
        $dto = $this->createTestDTO();
        $data = [
            'string' => 'value',
            'number' => 42,
            'array<mixed>' => [1, 2, 3],
            'null_value' => null,
        ];

        $this->assertEquals('value', $dto->testGetValue($data, 'string'));
        $this->assertEquals(42, $dto->testGetValue($data, 'number'));
        $this->assertEquals([1, 2, 3], $dto->testGetValue($data, 'array<mixed>'));
        $this->assertNull($dto->testGetValue($data, 'null_value'));
        $this->assertEquals('default', $dto->testGetValue($data, 'missing', 'default'));
        $this->assertNull($dto->testGetValue($data, 'missing'));
    }

    public function testValidationRulesAreAbstract(): void
    {
        $dto = $this->createTestDTO();
        $rules = $dto->testGetValidationRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('age', $rules);
        $this->assertArrayHasKey('active', $rules);
    }
}

/**
 * 測試用的 BaseDTO 具體實作.
 */
class TestableBaseDTO extends BaseDTO
{
    public string $name = '';

    public int $age = 0;

    public bool $active = false;

    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|min_length:2|max_length:50',
            'age' => 'required|integer|min:0|max:120',
            'active' => 'boolean',
        ];
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'age' => $this->age,
            'active' => $this->active,
        ];
    }

    // 公開 validate 方法用於測試
    public function testValidate(array $data): array
    {
        return $this->validate($data);
    }

    // 公開 helper 方法用於測試
    public function testGetString(array $data, string $key, ?string $default = null): ?string
    {
        return $this->getString($data, $key, $default);
    }

    public function testGetInt(array $data, string $key, ?int $default = null): ?int
    {
        return $this->getInt($data, $key, $default);
    }

    public function testGetBool(array $data, string $key, ?bool $default = null): ?bool
    {
        return $this->getBool($data, $key, $default);
    }

    public function testGetValue(array $data, string $key, mixed $default = null): mixed
    {
        return $this->getValue($data, $key, $default);
    }

    public function testGetValidationRules(): array
    {
        return $this->getValidationRules();
    }
}

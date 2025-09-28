<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\SourceType;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SourceType 值物件單元測試.
 */
final class SourceTypeTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_valid_parameters(): void
    {
        // Arrange
        $code = 'web';
        $name = '網頁介面';
        $description = '透過網頁界面建立的文章';

        // Act
        $sourceType = new SourceType($code, $name, $description);

        // Assert
        $this->assertSame($code, $sourceType->code);
        $this->assertSame($name, $sourceType->name);
        $this->assertSame($description, $sourceType->description);
    }

    #[Test]
    public function it_throws_exception_with_invalid_code(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid source code: invalid');

        // Act
        new SourceType('invalid', '測試名稱');
    }

    #[Test]
    public function it_throws_exception_with_empty_name(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source name cannot be empty');

        // Act
        new SourceType('web', '');
    }

    #[Test]
    public function it_throws_exception_with_meaningless_description(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Description cannot be meaningless string');

        // Act
        new SourceType('web', '網頁介面', 'N/A');
    }

    #[Test]
    public function it_accepts_case_insensitive_codes(): void
    {
        // Act
        $sourceType1 = new SourceType('WEB', '網頁介面');
        $sourceType2 = new SourceType('Api', 'API 介面');

        // Assert - 應該不拋出異常
        $this->assertSame('WEB', $sourceType1->code);
        $this->assertSame('Api', $sourceType2->code);
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        // Arrange
        $data = [
            'code' => 'api',
            'name' => 'API 介面',
            'description' => '透過 REST API 建立的文章',
        ];

        // Act
        $sourceType = SourceType::fromArray($data);

        // Assert
        $this->assertSame('api', $sourceType->code);
        $this->assertSame('API 介面', $sourceType->name);
        $this->assertSame('透過 REST API 建立的文章', $sourceType->description);
    }

    #[Test]
    public function it_throws_exception_when_creating_from_incomplete_array(): void
    {
        // Arrange
        /** @var array{code: string} $data */
        $data = [
            'code' => 'web',
            // 缺少 name
        ];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: code, name');

        // Act
        /** @phpstan-ignore-next-line */
        SourceType::fromArray($data);
    }

    #[Test]
    public function it_can_create_web_source_type(): void
    {
        // Act
        $sourceType = SourceType::createWeb();

        // Assert
        $this->assertSame('web', $sourceType->code);
        $this->assertSame('網頁介面', $sourceType->name);
        $this->assertStringContainsString('網頁', $sourceType->description);
    }

    #[Test]
    public function it_can_create_api_source_type(): void
    {
        // Act
        $sourceType = SourceType::createApi();

        // Assert
        $this->assertSame('api', $sourceType->code);
        $this->assertSame('API 介面', $sourceType->name);
        $this->assertStringContainsString('API', $sourceType->description);
    }

    #[Test]
    public function it_can_create_mobile_source_type(): void
    {
        // Act
        $sourceType = SourceType::createMobile();

        // Assert
        $this->assertSame('mobile', $sourceType->code);
        $this->assertSame('行動裝置', $sourceType->name);
        $this->assertStringContainsString('行動', $sourceType->description);
    }

    #[Test]
    public function it_can_create_import_source_type(): void
    {
        // Act
        $sourceType = SourceType::createImport();

        // Assert
        $this->assertSame('import', $sourceType->code);
        $this->assertSame('資料匯入', $sourceType->name);
        $this->assertStringContainsString('匯入', $sourceType->description);
    }

    #[Test]
    public function it_can_create_migration_source_type(): void
    {
        // Act
        $sourceType = SourceType::createMigration();

        // Assert
        $this->assertSame('migration', $sourceType->code);
        $this->assertSame('資料遷移', $sourceType->name);
        $this->assertStringContainsString('遷移', $sourceType->description);
    }

    #[Test]
    public function it_can_create_from_code(): void
    {
        // Act
        $webType = SourceType::fromCode('web');
        $apiType = SourceType::fromCode('API'); // 測試大寫
        $mobileType = SourceType::fromCode('Mobile');
        $importType = SourceType::fromCode('Import'); // 測試混合大小寫

        // Assert
        $this->assertSame('web', $webType->code);
        $this->assertSame('api', $apiType->code);
        $this->assertSame('mobile', $mobileType->code);
        $this->assertSame('import', $importType->code);
    }

    #[Test]
    public function it_throws_exception_when_creating_from_invalid_code(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid source code: invalid');

        // Act
        SourceType::fromCode('invalid');
    }

    #[Test]
    public function it_can_check_source_type(): void
    {
        // Arrange
        $webType = SourceType::createWeb();
        $apiType = SourceType::createApi();
        $importType = SourceType::createImport();
        $mobileType = SourceType::createMobile();
        $migrationType = SourceType::createMigration();

        // Act & Assert
        $this->assertTrue($webType->isWeb());
        $this->assertFalse($webType->isApi());
        $this->assertFalse($webType->isMobile());
        $this->assertFalse($webType->isImport());
        $this->assertFalse($webType->isMigration());

        $this->assertFalse($apiType->isWeb());
        $this->assertTrue($apiType->isApi());
        $this->assertFalse($apiType->isMobile());
        $this->assertFalse($apiType->isImport());
        $this->assertFalse($apiType->isMigration());

        $this->assertFalse($importType->isWeb());
        $this->assertFalse($importType->isApi());
        $this->assertTrue($importType->isImport());
        $this->assertFalse($importType->isMigration());

        $this->assertFalse($mobileType->isWeb());
        $this->assertFalse($mobileType->isApi());
        $this->assertTrue($mobileType->isMobile());
        $this->assertFalse($mobileType->isImport());
        $this->assertFalse($mobileType->isMigration());

        $this->assertFalse($migrationType->isWeb());
        $this->assertFalse($migrationType->isApi());
        $this->assertFalse($migrationType->isImport());
        $this->assertTrue($migrationType->isMigration());
    }

    #[Test]
    public function it_returns_correct_priority(): void
    {
        // Arrange
        $webType = SourceType::createWeb();
        $apiType = SourceType::createApi();
        $mobileType = SourceType::createMobile();
        $importType = SourceType::createImport();
        $migrationType = SourceType::createMigration();

        // Act & Assert
        $this->assertSame(1, $webType->getPriority());
        $this->assertSame(2, $apiType->getPriority());
        $this->assertSame(3, $mobileType->getPriority());
        $this->assertSame(4, $importType->getPriority());
        $this->assertSame(5, $migrationType->getPriority());
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        // Arrange
        $sourceType = SourceType::createWeb();

        // Act
        $array = $sourceType->toArray();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertSame('web', $array['code']);
        $this->assertSame('網頁介面', $array['name']);
    }

    #[Test]
    public function it_can_be_json_serialized(): void
    {
        // Arrange
        $sourceType = SourceType::createApi();

        // Act
        $json = json_encode($sourceType);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsString($json);
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($decoded);
        $this->assertSame('api', $decoded['code']);
        $this->assertSame('API 介面', $decoded['name']);
    }

    #[Test]
    public function it_can_check_equality(): void
    {
        // Arrange
        $sourceType1 = SourceType::createWeb();
        $sourceType2 = SourceType::createWeb();
        $sourceType3 = SourceType::createApi();
        $sourceType4 = new SourceType('web', '網頁介面', '不同描述');

        // Act & Assert
        $this->assertTrue($sourceType1->equals($sourceType2));
        $this->assertFalse($sourceType1->equals($sourceType3)); // 不同代碼
        $this->assertFalse($sourceType1->equals($sourceType4)); // 不同描述
    }

    #[Test]
    public function it_can_be_converted_to_string(): void
    {
        // Arrange
        $sourceTypeWithDesc = SourceType::createWeb();
        $sourceTypeNoDesc = new SourceType('web', '網頁介面');

        // Act
        $stringWithDesc = (string) $sourceTypeWithDesc;
        $stringNoDesc = (string) $sourceTypeNoDesc;

        // Assert
        $this->assertStringContainsString('網頁介面', $stringWithDesc);
        $this->assertStringContainsString('(', $stringWithDesc); // 包含描述的括號
        $this->assertSame('網頁介面', $stringNoDesc); // 沒有描述時不顯示括號
    }

    #[Test]
    public function it_returns_valid_codes(): void
    {
        // Act
        $validCodes = SourceType::getValidCodes();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($validCodes);
        $this->assertContains('web', $validCodes);
        $this->assertContains('api', $validCodes);
        $this->assertContains('mobile', $validCodes);
        $this->assertContains('import', $validCodes);
        $this->assertContains('migration', $validCodes);
        $this->assertCount(5, $validCodes);
    }

    #[Test]
    public function it_returns_all_types(): void
    {
        // Act
        $allTypes = SourceType::getAllTypes();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($allTypes);
        $this->assertCount(5, $allTypes);
        $this->assertContainsOnlyInstancesOf(SourceType::class, $allTypes);

        $codes = array_map(fn(SourceType $type) => $type->code, $allTypes);
        $this->assertContains('web', $codes);
        $this->assertContains('api', $codes);
        $this->assertContains('mobile', $codes);
        $this->assertContains('import', $codes);
        $this->assertContains('migration', $codes);
    }

    #[Test]
    public function it_handles_empty_description(): void
    {
        // Act
        $sourceType = new SourceType('web', '網頁介面');

        // Assert
        $this->assertSame('', $sourceType->description);
        $this->assertSame('網頁介面', (string) $sourceType); // 空描述時不顯示括號
    }
}

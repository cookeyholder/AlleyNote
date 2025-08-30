<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

interface SecurityTestInterface
{
    /**
     * 執行所有安全測試.
     *
     * @return array<mixed> 測試結果
     */
    public function runAllTests(): array;

    /**
     * 測試 Session 安全性.
     *
     * @return array<mixed> 測試結果
     */
    public function testSessionSecurity(): array;

    /**
     * 測試授權系統.
     *
     * @return array<mixed> 測試結果
     */
    public function testAuthorization(): array;

    /**
     * 測試檔案安全性.
     *
     * @return array<mixed> 測試結果
     */
    public function testFileSecurity(): array;

    /**
     * 測試安全標頭.
     *
     * @return array<mixed> 測試結果
     */
    public function testSecurityHeaders(): array;

    /**
     * 測試錯誤處理.
     *
     * @return array<mixed> 測試結果
     */
    public function testErrorHandling(): array;

    /**
     * 測試密碼安全性.
     *
     * @return array<mixed> 測試結果
     */
    public function testPasswordSecurity(): array;

    /**
     * 測試秘密管理.
     *
     * @return array<mixed> 測試結果
     */
    public function testSecretsManagement(): array;

    /**
     * 測試系統安全性.
     *
     * @return array<mixed> 測試結果
     */
    public function testSystemSecurity(): array;

    /**
     * 產生安全報告.
     *
     * @return array<mixed> 完整的安全報告
     */
    public function generateSecurityReport(): array;
}

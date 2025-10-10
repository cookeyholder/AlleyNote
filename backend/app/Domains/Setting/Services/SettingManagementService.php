<?php

declare(strict_types=1);

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Repositories\SettingRepository;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;
use RuntimeException;

/**
 * 系統設定管理服務.
 */
class SettingManagementService
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
    ) {}

    /**
     * 取得所有設定.
     *
     * @return array<string, mixed>
     */
    public function getAllSettings(): array
    {
        $settings = $this->settingRepository->findAll();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['key']] = [
                'value' => $setting['value'],
                'type' => $setting['type'],
                'description' => $setting['description'],
            ];
        }

        return $result;
    }

    /**
     * 取得單一設定.
     *
     * @return array<string, mixed>
     * @throws NotFoundException
     */
    public function getSetting(string $key): array
    {
        $setting = $this->settingRepository->findByKey($key);

        if (!$setting) {
            throw new NotFoundException("設定不存在 (Key: {$key})");
        }

        return [
            'key' => $setting['key'],
            'value' => $setting['value'],
            'type' => $setting['type'],
            'description' => $setting['description'],
        ];
    }

    /**
     * 批量更新設定.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function updateSettings(array $data): array
    {
        $errors = [];
        $updated = [];

        foreach ($data as $key => $value) {
            try {
                $updated[$key] = $this->updateSetting($key, $value);
            } catch (NotFoundException $e) {
                $errors[$key] = [$e->getMessage()];
            }
        }

        if (!empty($errors)) {
            $validationResult = new ValidationResult();
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $validationResult->addError($field, $message);
                }
            }

            throw new ValidationException($validationResult, '部分設定更新失敗');
        }

        return $updated;
    }

    /**
     * 更新單一設定.
     *
     * @return array<string, mixed>
     * @throws NotFoundException
     */
    public function updateSetting(string $key, mixed $value): array
    {
        $setting = $this->settingRepository->findByKey($key);

        if (!$setting) {
            throw new NotFoundException("設定不存在 (Key: {$key})");
        }

        $type = $setting['type'];
        $updatedSetting = $this->settingRepository->updateValue($key, $value, $type);

        if (!$updatedSetting) {
            throw new NotFoundException("設定更新失敗 (Key: {$key})");
        }

        return [
            'key' => $updatedSetting['key'],
            'value' => $updatedSetting['value'],
            'type' => $updatedSetting['type'],
            'description' => $updatedSetting['description'],
        ];
    }

    /**
     * 建立或更新設定.
     *
     * @return array<string, mixed>
     */
    public function upsertSetting(
        string $key,
        mixed $value,
        ?string $type = 'string',
        ?string $description = null,
    ): array {
        $setting = $this->settingRepository->findByKey($key);

        if ($setting) {
            $updatedSetting = $this->settingRepository->updateValue($key, $value, $type ?? $setting['type']);
            if (!$updatedSetting) {
                throw new RuntimeException("設定更新失敗 (Key: {$key})");
            }

            return [
                'key' => $updatedSetting['key'],
                'value' => $updatedSetting['value'],
                'type' => $updatedSetting['type'],
                'description' => $updatedSetting['description'],
            ];
        }

        $newSetting = $this->settingRepository->create($key, $value, $type ?? 'string', $description);

        return [
            'key' => $newSetting['key'],
            'value' => $newSetting['value'],
            'type' => $newSetting['type'],
            'description' => $newSetting['description'],
        ];
    }

    /**
     * 刪除設定.
     *
     * @throws NotFoundException
     */
    public function deleteSetting(string $key): void
    {
        $setting = $this->settingRepository->findByKey($key);

        if (!$setting) {
            throw new NotFoundException("設定不存在 (Key: {$key})");
        }

        $this->settingRepository->delete($key);
    }
}

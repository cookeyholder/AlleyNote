<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * API 錯誤碼定義.
 *
 * 錯誤碼格式: {模組}_{類型}_{編號}
 * - 模組: AUTH, USER, ROLE, PERM, POST, TAG, SETTING, SYSTEM
 * - 類型: VALIDATION, NOT_FOUND, FORBIDDEN, UNAUTHORIZED, CONFLICT, ERROR
 * - 編號: 001-999
 */
enum ErrorCode: string
{
    // ========================================
    // 通用錯誤 (1000-1999)
    // ========================================
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case NOT_FOUND = 'NOT_FOUND';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case BAD_REQUEST = 'BAD_REQUEST';
    case CONFLICT = 'CONFLICT';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    // ========================================
    // 認證相關錯誤 (2000-2999)
    // ========================================
    case AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    case AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    case AUTH_TOKEN_INVALID = 'AUTH_TOKEN_INVALID';
    case AUTH_TOKEN_MISSING = 'AUTH_TOKEN_MISSING';
    case AUTH_REFRESH_TOKEN_INVALID = 'AUTH_REFRESH_TOKEN_INVALID';
    case AUTH_USER_DISABLED = 'AUTH_USER_DISABLED';
    case AUTH_EMAIL_NOT_VERIFIED = 'AUTH_EMAIL_NOT_VERIFIED';
    case AUTH_PASSWORD_INCORRECT = 'AUTH_PASSWORD_INCORRECT';

    // ========================================
    // 使用者相關錯誤 (3000-3999)
    // ========================================
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case USER_ALREADY_EXISTS = 'USER_ALREADY_EXISTS';
    case USER_EMAIL_EXISTS = 'USER_EMAIL_EXISTS';
    case USER_USERNAME_EXISTS = 'USER_USERNAME_EXISTS';
    case USER_VALIDATION_ERROR = 'USER_VALIDATION_ERROR';
    case USER_CANNOT_DELETE_SELF = 'USER_CANNOT_DELETE_SELF';
    case USER_CANNOT_MODIFY_ADMIN = 'USER_CANNOT_MODIFY_ADMIN';

    // ========================================
    // 角色相關錯誤 (4000-4999)
    // ========================================
    case ROLE_NOT_FOUND = 'ROLE_NOT_FOUND';
    case ROLE_ALREADY_EXISTS = 'ROLE_ALREADY_EXISTS';
    case ROLE_IN_USE = 'ROLE_IN_USE';
    case ROLE_VALIDATION_ERROR = 'ROLE_VALIDATION_ERROR';
    case ROLE_CANNOT_DELETE_SYSTEM = 'ROLE_CANNOT_DELETE_SYSTEM';

    // ========================================
    // 權限相關錯誤 (5000-5999)
    // ========================================
    case PERMISSION_NOT_FOUND = 'PERMISSION_NOT_FOUND';
    case PERMISSION_DENIED = 'PERMISSION_DENIED';
    case PERMISSION_VALIDATION_ERROR = 'PERMISSION_VALIDATION_ERROR';

    // ========================================
    // 文章相關錯誤 (6000-6999)
    // ========================================
    case POST_NOT_FOUND = 'POST_NOT_FOUND';
    case POST_VALIDATION_ERROR = 'POST_VALIDATION_ERROR';
    case POST_ALREADY_PUBLISHED = 'POST_ALREADY_PUBLISHED';
    case POST_NOT_PUBLISHED = 'POST_NOT_PUBLISHED';
    case POST_CANNOT_MODIFY = 'POST_CANNOT_MODIFY';

    // ========================================
    // 標籤相關錯誤 (7000-7999)
    // ========================================
    case TAG_NOT_FOUND = 'TAG_NOT_FOUND';
    case TAG_ALREADY_EXISTS = 'TAG_ALREADY_EXISTS';
    case TAG_IN_USE = 'TAG_IN_USE';
    case TAG_VALIDATION_ERROR = 'TAG_VALIDATION_ERROR';

    // ========================================
    // 設定相關錯誤 (8000-8999)
    // ========================================
    case SETTING_NOT_FOUND = 'SETTING_NOT_FOUND';
    case SETTING_VALIDATION_ERROR = 'SETTING_VALIDATION_ERROR';
    case SETTING_READONLY = 'SETTING_READONLY';

    // ========================================
    // 附件相關錯誤 (9000-9999)
    // ========================================
    case ATTACHMENT_NOT_FOUND = 'ATTACHMENT_NOT_FOUND';
    case ATTACHMENT_UPLOAD_FAILED = 'ATTACHMENT_UPLOAD_FAILED';
    case ATTACHMENT_SIZE_EXCEEDED = 'ATTACHMENT_SIZE_EXCEEDED';
    case ATTACHMENT_TYPE_NOT_ALLOWED = 'ATTACHMENT_TYPE_NOT_ALLOWED';

    /**
     * 取得錯誤碼的 HTTP 狀態碼.
     */
    public function getHttpStatus(): int
    {
        return match ($this) {
            self::NOT_FOUND,
            self::USER_NOT_FOUND,
            self::ROLE_NOT_FOUND,
            self::PERMISSION_NOT_FOUND,
            self::POST_NOT_FOUND,
            self::TAG_NOT_FOUND,
            self::SETTING_NOT_FOUND,
            self::ATTACHMENT_NOT_FOUND => 404,

            self::UNAUTHORIZED,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_TOKEN_INVALID,
            self::AUTH_TOKEN_MISSING,
            self::AUTH_INVALID_CREDENTIALS,
            self::AUTH_PASSWORD_INCORRECT => 401,

            self::FORBIDDEN,
            self::PERMISSION_DENIED,
            self::AUTH_USER_DISABLED,
            self::AUTH_EMAIL_NOT_VERIFIED,
            self::USER_CANNOT_MODIFY_ADMIN,
            self::POST_CANNOT_MODIFY => 403,

            self::CONFLICT,
            self::USER_ALREADY_EXISTS,
            self::USER_EMAIL_EXISTS,
            self::USER_USERNAME_EXISTS,
            self::ROLE_ALREADY_EXISTS,
            self::ROLE_IN_USE,
            self::TAG_ALREADY_EXISTS,
            self::TAG_IN_USE,
            self::POST_ALREADY_PUBLISHED => 409,

            self::VALIDATION_ERROR,
            self::USER_VALIDATION_ERROR,
            self::ROLE_VALIDATION_ERROR,
            self::PERMISSION_VALIDATION_ERROR,
            self::POST_VALIDATION_ERROR,
            self::TAG_VALIDATION_ERROR,
            self::SETTING_VALIDATION_ERROR => 422,

            self::TOO_MANY_REQUESTS => 429,

            self::SERVICE_UNAVAILABLE => 503,

            self::BAD_REQUEST,
            self::SETTING_READONLY,
            self::ATTACHMENT_SIZE_EXCEEDED,
            self::ATTACHMENT_TYPE_NOT_ALLOWED => 400,

            default => 500,
        };
    }

    /**
     * 取得錯誤碼的描述訊息.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::VALIDATION_ERROR => '資料驗證失敗',
            self::NOT_FOUND => '資源不存在',
            self::UNAUTHORIZED => '未授權',
            self::FORBIDDEN => '禁止訪問',
            self::INTERNAL_ERROR => '系統內部錯誤',
            self::BAD_REQUEST => '請求格式錯誤',
            self::CONFLICT => '資源衝突',
            self::TOO_MANY_REQUESTS => '請求過於頻繁',
            self::SERVICE_UNAVAILABLE => '服務暫時無法使用',

            self::AUTH_INVALID_CREDENTIALS => '帳號或密碼錯誤',
            self::AUTH_TOKEN_EXPIRED => 'Token 已過期',
            self::AUTH_TOKEN_INVALID => 'Token 無效',
            self::AUTH_TOKEN_MISSING => '缺少 Token',
            self::AUTH_REFRESH_TOKEN_INVALID => 'Refresh Token 無效',
            self::AUTH_USER_DISABLED => '帳號已被停用',
            self::AUTH_EMAIL_NOT_VERIFIED => '電子郵件尚未驗證',
            self::AUTH_PASSWORD_INCORRECT => '密碼錯誤',

            self::USER_NOT_FOUND => '使用者不存在',
            self::USER_ALREADY_EXISTS => '使用者已存在',
            self::USER_EMAIL_EXISTS => '電子郵件已被使用',
            self::USER_USERNAME_EXISTS => '使用者名稱已被使用',
            self::USER_VALIDATION_ERROR => '使用者資料驗證失敗',
            self::USER_CANNOT_DELETE_SELF => '無法刪除自己的帳號',
            self::USER_CANNOT_MODIFY_ADMIN => '無法修改管理員帳號',

            self::ROLE_NOT_FOUND => '角色不存在',
            self::ROLE_ALREADY_EXISTS => '角色已存在',
            self::ROLE_IN_USE => '角色仍被使用中',
            self::ROLE_VALIDATION_ERROR => '角色資料驗證失敗',
            self::ROLE_CANNOT_DELETE_SYSTEM => '無法刪除系統角色',

            self::PERMISSION_NOT_FOUND => '權限不存在',
            self::PERMISSION_DENIED => '權限不足',
            self::PERMISSION_VALIDATION_ERROR => '權限資料驗證失敗',

            self::POST_NOT_FOUND => '文章不存在',
            self::POST_VALIDATION_ERROR => '文章資料驗證失敗',
            self::POST_ALREADY_PUBLISHED => '文章已發布',
            self::POST_NOT_PUBLISHED => '文章尚未發布',
            self::POST_CANNOT_MODIFY => '無法修改此文章',

            self::TAG_NOT_FOUND => '標籤不存在',
            self::TAG_ALREADY_EXISTS => '標籤已存在',
            self::TAG_IN_USE => '標籤仍被使用中',
            self::TAG_VALIDATION_ERROR => '標籤資料驗證失敗',

            self::SETTING_NOT_FOUND => '設定不存在',
            self::SETTING_VALIDATION_ERROR => '設定資料驗證失敗',
            self::SETTING_READONLY => '此設定為唯讀',

            self::ATTACHMENT_NOT_FOUND => '附件不存在',
            self::ATTACHMENT_UPLOAD_FAILED => '附件上傳失敗',
            self::ATTACHMENT_SIZE_EXCEEDED => '附件大小超過限制',
            self::ATTACHMENT_TYPE_NOT_ALLOWED => '不支援的附件類型',
        };
    }

    /**
     * 轉換為陣列格式.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->value,
            'http_status' => $this->getHttpStatus(),
            'description' => $this->getDescription(),
        ];
    }
}

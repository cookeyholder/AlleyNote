<?php

declare(strict_types=1);

namespace App\Domains\Security\Enums;

enum ActivityType: string
{
    case ACCESS_DENIED = 'access_denied';
    case ATTACHMENT_DELETED = 'attachment_deleted';
    case ATTACHMENT_DOWNLOADED = 'attachment_downloaded';
    case ATTACHMENT_PERMISSION_DENIED = 'attachment_permission_denied';
    case ATTACHMENT_SIZE_EXCEEDED = 'attachment_size_exceeded';
    case ATTACHMENT_UPLOADED = 'attachment_uploaded';
    case ATTACHMENT_VIRUS_DETECTED = 'attachment_virus_detected';
    case CSRF_ATTACK_BLOCKED = 'csrf_attack_blocked';
    case IP_BLOCKED = 'ip_blocked';
    case IP_UNBLOCKED = 'ip_unblocked';
    case LOGIN_FAILED = 'login_failed';
    case LOGIN_SUCCESS = 'login_success';
    case LOGOUT = 'logout';
    case PASSWORD_CHANGED = 'password_changed';
    case POST_CREATED = 'post_created';
    case POST_DELETED = 'post_deleted';
    case POST_PINNED = 'post_pinned';
    case POST_PUBLISHED = 'post_published';
    case POST_UNPINNED = 'post_unpinned';
    case POST_UPDATED = 'post_updated';
    case POST_VIEWED = 'post_viewed';
    case SECURITY_ACTIVITY_SCAN_COMPLETED = 'security_activity_scan_completed';
    case SQL_INJECTION_BLOCKED = 'sql_injection_blocked';
    case SUSPICIOUS_ACTIVITY_DETECTED = 'suspicious_activity_detected';
    case USER_REGISTERED = 'user_registered';
    case XSS_ATTACK_BLOCKED = 'xss_attack_blocked';

    public function getCategory(): ActivityCategory
    {
        return match ($this) {
            self::LOGIN_SUCCESS,
            self::LOGIN_FAILED,
            self::LOGOUT,
            self::USER_REGISTERED,
            self::PASSWORD_CHANGED,
            self::ACCESS_DENIED => ActivityCategory::AUTHENTICATION,

            self::POST_CREATED,
            self::POST_UPDATED,
            self::POST_DELETED,
            self::POST_VIEWED,
            self::POST_PUBLISHED,
            self::POST_PINNED,
            self::POST_UNPINNED => ActivityCategory::CONTENT,

            self::ATTACHMENT_UPLOADED,
            self::ATTACHMENT_DOWNLOADED,
            self::ATTACHMENT_DELETED,
            self::ATTACHMENT_PERMISSION_DENIED,
            self::ATTACHMENT_SIZE_EXCEEDED,
            self::ATTACHMENT_VIRUS_DETECTED => ActivityCategory::ATTACHMENT,

            self::CSRF_ATTACK_BLOCKED,
            self::XSS_ATTACK_BLOCKED,
            self::SQL_INJECTION_BLOCKED,
            self::IP_BLOCKED,
            self::IP_UNBLOCKED,
            self::SUSPICIOUS_ACTIVITY_DETECTED,
            self::SECURITY_ACTIVITY_SCAN_COMPLETED => ActivityCategory::SECURITY,
        };
    }

    public function getSeverity(): ActivitySeverity
    {
        return match ($this) {
            self::CSRF_ATTACK_BLOCKED,
            self::XSS_ATTACK_BLOCKED,
            self::SQL_INJECTION_BLOCKED,
            self::ATTACHMENT_VIRUS_DETECTED,
            self::IP_BLOCKED,
            self::SUSPICIOUS_ACTIVITY_DETECTED => ActivitySeverity::CRITICAL,

            self::LOGIN_FAILED,
            self::ATTACHMENT_PERMISSION_DENIED,
            self::ATTACHMENT_SIZE_EXCEEDED,
            self::ACCESS_DENIED => ActivitySeverity::HIGH,

            self::POST_DELETED,
            self::PASSWORD_CHANGED,
            self::SECURITY_ACTIVITY_SCAN_COMPLETED,
            self::IP_UNBLOCKED => ActivitySeverity::MEDIUM,

            self::POST_UPDATED,
            self::POST_PINNED,
            self::POST_UNPINNED,
            self::POST_PUBLISHED,
            self::ATTACHMENT_UPLOADED,
            self::ATTACHMENT_DELETED => ActivitySeverity::NORMAL,

            self::LOGIN_SUCCESS,
            self::LOGOUT,
            self::POST_CREATED,
            self::POST_VIEWED,
            self::ATTACHMENT_DOWNLOADED,
            self::USER_REGISTERED => ActivitySeverity::LOW,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ACCESS_DENIED => '存取被拒絕',
            self::ATTACHMENT_DELETED => '附件已刪除',
            self::ATTACHMENT_DOWNLOADED => '附件已下載',
            self::ATTACHMENT_PERMISSION_DENIED => '附件存取權限不足',
            self::ATTACHMENT_SIZE_EXCEEDED => '附件大小超出限制',
            self::ATTACHMENT_UPLOADED => '附件已上傳',
            self::ATTACHMENT_VIRUS_DETECTED => '附件偵測到可疑內容',
            self::CSRF_ATTACK_BLOCKED => '阻擋 CSRF 攻擊',
            self::IP_BLOCKED => 'IP 已封鎖',
            self::IP_UNBLOCKED => 'IP 已解除封鎖',
            self::LOGIN_FAILED => '登入失敗',
            self::LOGIN_SUCCESS => '登入成功',
            self::LOGOUT => '使用者登出',
            self::PASSWORD_CHANGED => '密碼已變更',
            self::POST_CREATED => '文章已建立',
            self::POST_DELETED => '文章已刪除',
            self::POST_PINNED => '文章已置頂',
            self::POST_PUBLISHED => '文章已發布',
            self::POST_UNPINNED => '文章已取消置頂',
            self::POST_UPDATED => '文章已更新',
            self::POST_VIEWED => '文章已檢視',
            self::SECURITY_ACTIVITY_SCAN_COMPLETED => '安全活動掃描完成',
            self::SQL_INJECTION_BLOCKED => '阻擋 SQL 注入攻擊',
            self::SUSPICIOUS_ACTIVITY_DETECTED => '偵測到可疑活動',
            self::USER_REGISTERED => '使用者已註冊',
            self::XSS_ATTACK_BLOCKED => '阻擋 XSS 攻擊',
        };
    }

    public function isFailureAction(): bool
    {
        return match ($this) {
            self::ACCESS_DENIED,
            self::LOGIN_FAILED,
            self::ATTACHMENT_PERMISSION_DENIED,
            self::ATTACHMENT_SIZE_EXCEEDED,
            self::ATTACHMENT_VIRUS_DETECTED,
            self::CSRF_ATTACK_BLOCKED,
            self::SQL_INJECTION_BLOCKED,
            self::XSS_ATTACK_BLOCKED,
            self::IP_BLOCKED,
            self::SUSPICIOUS_ACTIVITY_DETECTED => true,
            default => false,
        };
    }

    public function isSecurityRelated(): bool
    {
        return $this->getCategory() === ActivityCategory::SECURITY || $this->isFailureAction();
    }
}

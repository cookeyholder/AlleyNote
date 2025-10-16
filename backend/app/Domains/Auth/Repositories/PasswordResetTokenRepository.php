<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\PasswordResetTokenRepositoryInterface;
use App\Domains\Auth\Entities\PasswordResetToken;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * 密碼重設憑證儲存庫（PDO 實作）.
 */
final class PasswordResetTokenRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(private readonly PDO $db) {}

    public function create(PasswordResetToken $token): PasswordResetToken
    {
        $sql = 'INSERT INTO password_reset_tokens (
            user_id, token_hash, expires_at, created_at, requested_ip, requested_user_agent
        ) VALUES (:user_id, :token_hash, :expires_at, :created_at, :requested_ip, :requested_user_agent)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $token->getUserId(),
            'token_hash' => $token->getTokenHash(),
            'expires_at' => $token->getExpiresAt()->format('Y-m-d H:i:s'),
            'created_at' => $token->getCreatedAt()->format('Y-m-d H:i:s'),
            'requested_ip' => $token->getRequestedIp(),
            'requested_user_agent' => $token->getRequestedUserAgent(),
        ]);

        $id = (int) $this->db->lastInsertId();

        return $token->withPersistenceState($id);
    }

    public function findValidByHash(string $tokenHash, DateTimeImmutable $now): ?PasswordResetToken
    {
        $sql = 'SELECT * FROM password_reset_tokens
                WHERE token_hash = :token_hash
                  AND (used_at IS NULL)
                  AND expires_at > :now
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'token_hash' => $tokenHash,
            'now' => $now->format('Y-m-d H:i:s'),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */

        return $this->mapRowToEntity($row);
    }

    public function invalidateForUser(int $userId): void
    {
        $sql = 'DELETE FROM password_reset_tokens WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
    }

    public function markAsUsed(PasswordResetToken $token): void
    {
        $sql = 'UPDATE password_reset_tokens
                SET used_at = :used_at,
                    used_ip = :used_ip,
                    used_user_agent = :used_user_agent
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $token->getId(),
            'used_at' => $token->getUsedAt()?->format('Y-m-d H:i:s'),
            'used_ip' => $token->getUsedIp(),
            'used_user_agent' => $token->getUsedUserAgent(),
        ]);
    }

    public function cleanupExpired(DateTimeImmutable $now): int
    {
        $sql = 'DELETE FROM password_reset_tokens WHERE expires_at <= :now';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['now' => $now->format('Y-m-d H:i:s')]);

        return $stmt->rowCount();
    }

    /**
     * @param array<int|string, mixed> $row
     */
    private function mapRowToEntity(array $row): PasswordResetToken
    {
        if (!isset($row['id'], $row['user_id'], $row['token_hash'], $row['expires_at'], $row['created_at'])) {
            throw new RuntimeException('密碼重設 Token 資料不完整');
        }

        $idValue = $row['id'];
        if (!is_int($idValue) && !is_numeric($idValue)) {
            throw new RuntimeException('無法解析密碼重設 Token 的 ID');
        }

        $userIdValue = $row['user_id'];
        if (!is_int($userIdValue) && !is_numeric($userIdValue)) {
            throw new RuntimeException('無法解析密碼重設 Token 的使用者 ID');
        }

        $tokenHashValue = $row['token_hash'];
        if (!is_string($tokenHashValue)) {
            throw new RuntimeException('無法解析密碼重設 Token 雜湊值');
        }

        $expiresAtValue = $row['expires_at'];
        $createdAtValue = $row['created_at'];

        if (!is_string($expiresAtValue) || !is_string($createdAtValue)) {
            throw new RuntimeException('密碼重設 Token 的時間欄位格式錯誤');
        }

        $requestedIpValue = $row['requested_ip'] ?? null;
        $requestedUserAgentValue = $row['requested_user_agent'] ?? null;
        $usedAtValue = $row['used_at'] ?? null;
        $usedIpValue = $row['used_ip'] ?? null;
        $usedUserAgentValue = $row['used_user_agent'] ?? null;

        return new PasswordResetToken(
            id: (int) $idValue,
            userId: (int) $userIdValue,
            tokenHash: $tokenHashValue,
            expiresAt: new DateTimeImmutable($expiresAtValue),
            createdAt: new DateTimeImmutable($createdAtValue),
            requestedIp: is_string($requestedIpValue) ? $requestedIpValue : null,
            requestedUserAgent: is_string($requestedUserAgentValue) ? $requestedUserAgentValue : null,
            usedAt: is_string($usedAtValue) ? new DateTimeImmutable($usedAtValue) : null,
            usedIp: is_string($usedIpValue) ? $usedIpValue : null,
            usedUserAgent: is_string($usedUserAgentValue) ? $usedUserAgentValue : null,
        );
    }
}

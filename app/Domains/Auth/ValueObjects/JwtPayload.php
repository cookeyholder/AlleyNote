<?php

declare(strict_types=1);

namespace AlleyNote\Domains\Auth\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * JWT Payload Value Object
 * 
 * 表示 JWT Token 的載荷資訊，包含所有標準和自訂宣告。
 * 此類別是不可變的，確保 payload 資料的完整性。
 * 
 * @author GitHub Copilot
 * @since 1.0.0
 */
final readonly class JwtPayload implements JsonSerializable
{
    /**
     * 建構 JWT Payload
     * 
     * @param string $jti JWT 唯一識別符 (JWT ID)
     * @param string $sub 主題，通常是使用者 ID (Subject)
     * @param string $iss 發行者 (Issuer)
     * @param array<string> $aud 受眾 (Audience)
     * @param DateTimeImmutable $iat 發行時間 (Issued At)
     * @param DateTimeImmutable $exp 過期時間 (Expiration)
     * @param DateTimeImmutable|null $nbf 生效時間 (Not Before)
     * @param array<string, mixed> $customClaims 自訂宣告
     * 
     * @throws InvalidArgumentException 當參數無效時
     */
    public function __construct(
        private string $jti,
        private string $sub,
        private string $iss,
        private array $aud,
        private DateTimeImmutable $iat,
        private DateTimeImmutable $exp,
        private ?DateTimeImmutable $nbf = null,
        private array $customClaims = []
    ) {
        $this->validateJti($jti);
        $this->validateSub($sub);
        $this->validateIss($iss);
        $this->validateAud($aud);
        $this->validateTimes($iat, $exp, $nbf);
        $this->validateCustomClaims($customClaims);
    }

    /**
     * 從陣列建立 JWT Payload
     * 
     * @param array<string, mixed> $data JWT payload 資料
     * @return self
     * @throws InvalidArgumentException 當資料格式無效時
     */
    public static function fromArray(array $data): self
    {
        $requiredFields = ['jti', 'sub', 'iss', 'aud', 'iat', 'exp'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $iat = is_int($data['iat'])
            ? new DateTimeImmutable('@' . $data['iat'])
            : new DateTimeImmutable($data['iat']);

        $exp = is_int($data['exp'])
            ? new DateTimeImmutable('@' . $data['exp'])
            : new DateTimeImmutable($data['exp']);

        $nbf = isset($data['nbf'])
            ? (is_int($data['nbf'])
                ? new DateTimeImmutable('@' . $data['nbf'])
                : new DateTimeImmutable($data['nbf']))
            : null;

        $aud = is_array($data['aud']) ? $data['aud'] : [$data['aud']];

        // 提取自訂宣告 (排除標準宣告)
        $standardClaims = ['jti', 'sub', 'iss', 'aud', 'iat', 'exp', 'nbf'];
        $customClaims = array_diff_key($data, array_flip($standardClaims));

        return new self(
            jti: $data['jti'],
            sub: $data['sub'],
            iss: $data['iss'],
            aud: $aud,
            iat: $iat,
            exp: $exp,
            nbf: $nbf,
            customClaims: $customClaims
        );
    }

    /**
     * 取得 JWT ID
     * 
     * @return string
     */
    public function getJti(): string
    {
        return $this->jti;
    }

    /**
     * 取得主題 (通常是使用者 ID)
     * 
     * @return string
     */
    public function getSubject(): string
    {
        return $this->sub;
    }

    /**
     * 取得使用者 ID (subject 的別名)
     * 
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $this->sub;
    }

    /**
     * 取得發行者
     * 
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->iss;
    }

    /**
     * 取得受眾
     * 
     * @return array<string>
     */
    public function getAudience(): array
    {
        return $this->aud;
    }

    /**
     * 取得發行時間
     * 
     * @return DateTimeImmutable
     */
    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->iat;
    }

    /**
     * 取得過期時間
     * 
     * @return DateTimeImmutable
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->exp;
    }

    /**
     * 取得生效時間
     * 
     * @return DateTimeImmutable|null
     */
    public function getNotBefore(): ?DateTimeImmutable
    {
        return $this->nbf;
    }

    /**
     * 取得自訂宣告
     * 
     * @return array<string, mixed>
     */
    public function getCustomClaims(): array
    {
        return $this->customClaims;
    }

    /**
     * 取得特定自訂宣告
     * 
     * @param string $claim 宣告名稱
     * @return mixed|null
     */
    public function getCustomClaim(string $claim): mixed
    {
        return $this->customClaims[$claim] ?? null;
    }

    /**
     * 檢查是否已過期
     * 
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     * @return bool
     */
    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable();
        return $this->exp <= $now;
    }

    /**
     * 檢查是否已生效
     * 
     * @param DateTimeImmutable|null $now 檢查時間，預設為現在
     * @return bool
     */
    public function isActive(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable();

        // 如果有 nbf，檢查是否已生效
        if ($this->nbf !== null && $this->nbf > $now) {
            return false;
        }

        // 檢查是否未過期
        return !$this->isExpired($now);
    }

    /**
     * 檢查是否包含特定受眾
     * 
     * @param string $audience 受眾
     * @return bool
     */
    public function hasAudience(string $audience): bool
    {
        return in_array($audience, $this->aud, true);
    }

    /**
     * 轉換為陣列格式（用於 JWT 編碼）
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'jti' => $this->jti,
            'sub' => $this->sub,
            'iss' => $this->iss,
            'aud' => count($this->aud) === 1 ? $this->aud[0] : $this->aud,
            'iat' => $this->iat->getTimestamp(),
            'exp' => $this->exp->getTimestamp(),
        ];

        if ($this->nbf !== null) {
            $payload['nbf'] = $this->nbf->getTimestamp();
        }

        // 合併自訂宣告
        return array_merge($payload, $this->customClaims);
    }

    /**
     * JsonSerializable 實作
     * 
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查與另一個 JwtPayload 是否相等
     * 
     * @param JwtPayload $other 另一個 JwtPayload
     * @return bool
     */
    public function equals(JwtPayload $other): bool
    {
        return $this->jti === $other->jti
            && $this->sub === $other->sub
            && $this->iss === $other->iss
            && $this->aud === $other->aud
            && $this->iat->getTimestamp() === $other->iat->getTimestamp()
            && $this->exp->getTimestamp() === $other->exp->getTimestamp()
            && ($this->nbf?->getTimestamp()) === ($other->nbf?->getTimestamp())
            && $this->customClaims === $other->customClaims;
    }

    /**
     * 轉換為字串表示
     * 
     * @return string
     */
    public function toString(): string
    {
        $nbf = $this->nbf?->format('Y-m-d H:i:s') ?? 'null';
        $customClaimsCount = count($this->customClaims);

        return sprintf(
            'JwtPayload(jti=%s, sub=%s, iss=%s, aud=[%s], iat=%s, exp=%s, nbf=%s, customClaims=%d)',
            $this->jti,
            $this->sub,
            $this->iss,
            implode(', ', $this->aud),
            $this->iat->format('Y-m-d H:i:s'),
            $this->exp->format('Y-m-d H:i:s'),
            $nbf,
            $customClaimsCount
        );
    }

    /**
     * __toString 魔術方法
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * 驗證 JWT ID
     * 
     * @param string $jti JWT ID
     * @throws InvalidArgumentException 當 JTI 無效時
     */
    private function validateJti(string $jti): void
    {
        if (empty($jti)) {
            throw new InvalidArgumentException('JWT ID (jti) cannot be empty');
        }

        if (mb_strlen($jti) > 255) {
            throw new InvalidArgumentException('JWT ID (jti) cannot exceed 255 characters');
        }
    }

    /**
     * 驗證主題
     * 
     * @param string $sub 主題
     * @throws InvalidArgumentException 當主題無效時
     */
    private function validateSub(string $sub): void
    {
        if ($sub === '') {
            throw new InvalidArgumentException('Subject (sub) cannot be empty');
        }

        // 檢查是否為有效的使用者 ID
        if (!is_numeric($sub) || (int) $sub <= 0) {
            throw new InvalidArgumentException('Subject (sub) must be a valid positive integer');
        }
    }

    /**
     * 驗證發行者
     * 
     * @param string $iss 發行者
     * @throws InvalidArgumentException 當發行者無效時
     */
    private function validateIss(string $iss): void
    {
        if (empty($iss)) {
            throw new InvalidArgumentException('Issuer (iss) cannot be empty');
        }
    }

    /**
     * 驗證受眾
     * 
     * @param array<string> $aud 受眾陣列
     * @throws InvalidArgumentException 當受眾無效時
     */
    private function validateAud(array $aud): void
    {
        if (empty($aud)) {
            throw new InvalidArgumentException('Audience (aud) cannot be empty');
        }

        foreach ($aud as $audience) {
            if (!is_string($audience) || empty($audience)) {
                throw new InvalidArgumentException('All audience values must be non-empty strings');
            }
        }
    }

    /**
     * 驗證時間相關宣告
     * 
     * @param DateTimeImmutable $iat 發行時間
     * @param DateTimeImmutable $exp 過期時間
     * @param DateTimeImmutable|null $nbf 生效時間
     * @throws InvalidArgumentException 當時間設定無效時
     */
    private function validateTimes(DateTimeImmutable $iat, DateTimeImmutable $exp, ?DateTimeImmutable $nbf): void
    {
        if ($exp <= $iat) {
            throw new InvalidArgumentException('Expiration time (exp) must be after issued time (iat)');
        }

        if ($nbf !== null && $nbf > $exp) {
            throw new InvalidArgumentException('Not before time (nbf) cannot be after expiration time (exp)');
        }
    }

    /**
     * 驗證自訂宣告
     * 
     * @param array<string, mixed> $customClaims 自訂宣告
     * @throws InvalidArgumentException 當自訂宣告無效時
     */
    private function validateCustomClaims(array $customClaims): void
    {
        $reservedClaims = ['jti', 'sub', 'iss', 'aud', 'iat', 'exp', 'nbf'];

        foreach (array_keys($customClaims) as $claim) {
            if (in_array($claim, $reservedClaims, true)) {
                throw new InvalidArgumentException("Cannot use reserved claim '{$claim}' as custom claim");
            }

            if (!is_string($claim) || empty($claim)) {
                throw new InvalidArgumentException('Custom claim names must be non-empty strings');
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Routing\Contracts\MiddlewareInterface;
use App\Infrastructure\Routing\Contracts\RequestHandlerInterface;
use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JWT 授權中介軟體.
 *
 * 負責驗證已認證使用者的角色和權限，確保使用者有足夠的權限執行請求的操作。
 * 支援基於角色的存取控制（RBAC）和基於屬性的存取控制（ABAC）。
 *
 * 設計靈感來自 Action Policy 和 Pundit 授權框架。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class JwtAuthorizationMiddleware implements MiddlewareInterface
{
    /**
     * 中介軟體優先順序（數值越小優先級越高）.
     * 必須在 JwtAuthenticationMiddleware 之後執行。
     */
    private const DEFAULT_PRIORITY = 20;

    /**
     * 中介軟體名稱.
     */
    private const MIDDLEWARE_NAME = 'jwt-authorization';

    /**
     * 預設授權策略.
     */
    private const DEFAULT_POLICY = 'deny'; // 預設拒絕

    /**
     * 預設的系統管理員角色.
     */
    private const ADMIN_ROLES = ['admin', 'super_admin', 'system_admin'];

    /**
     * 授權策略配置.
     *
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(
        private int $priority = self::DEFAULT_PRIORITY,
        private bool $enabled = true,
        array $config = [],
    ) {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 處理 JWT 授權請求.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param RequestHandlerInterface $handler 請求處理器
     * @return ResponseInterface HTTP 回應物件
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->enabled || !$this->shouldProcess($request)) {
            return $handler->handle($request);
        }

        try {
            // 1. 檢查使用者是否已認證
            if (!$request->getAttribute('authenticated', false)) {
                return $this->createForbiddenResponse('使用者未認證', 'NOT_AUTHENTICATED');
            }

            // 2. 提取使用者資訊
            $userRole = $request->getAttribute('role');
            $userPermissions = $request->getAttribute('permissions', []);
            $userId = $request->getAttribute('user_id');

            // 3. 判斷請求的資源和操作
            $resource = $this->extractResource($request);
            $action = $this->extractAction($request);

            // 4. 執行授權檢查
            $authorizationResult = $this->authorize(
                userId: $userId,
                userRole: $userRole,
                userPermissions: $userPermissions,
                resource: $resource,
                action: $action,
                request: $request,
            );

            if (!$authorizationResult->isAllowed()) {
                return $this->createForbiddenResponse(
                    $authorizationResult->getReason(),
                    $authorizationResult->getCode(),
                );
            }

            // 5. 將授權資訊注入到請求中
            $request = $this->injectAuthorizationContext($request, $authorizationResult);

            return $handler->handle($request);
        } catch (Exception $e) {
            return $this->createForbiddenResponse('授權檢查失敗', 'AUTHORIZATION_ERROR');
        }
    }

    /**
     * 執行授權檢查.
     *
     * @param int $userId 使用者 ID
     * @param string|null $userRole 使用者角色
     * @param array<string> $userPermissions 使用者權限
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return AuthorizationResult 授權結果
     */
    private function authorize(
        int $userId,
        ?string $userRole,
        array $userPermissions,
        string $resource,
        string $action,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        // 1. 超級管理員檢查
        if ($this->isSuperAdmin($userRole)) {
            return new AuthorizationResult(
                allowed: true,
                reason: '超級管理員擁有所有權限',
                code: 'SUPER_ADMIN_ACCESS',
                appliedRules: ['super_admin'],
            );
        }

        // 2. 基於角色的授權檢查 (RBAC)
        $roleResult = $this->authorizeByRole($userRole, $resource, $action);
        if ($roleResult->isAllowed()) {
            return $roleResult;
        }

        // 3. 基於權限的授權檢查 (Permission-based)
        $permissionResult = $this->authorizeByPermission($userPermissions, $resource, $action);
        if ($permissionResult->isAllowed()) {
            return $permissionResult;
        }

        // 4. 基於屬性的授權檢查 (ABAC)
        $attributeResult = $this->authorizeByAttributes($userId, $userRole, $resource, $action, $request);
        if ($attributeResult->isAllowed()) {
            return $attributeResult;
        }

        // 5. 自訂授權策略
        $customResult = $this->authorizeByCustomRules($userId, $userRole, $userPermissions, $resource, $action, $request);
        if ($customResult->isAllowed()) {
            return $customResult;
        }

        // 6. 預設拒絕
        return new AuthorizationResult(
            allowed: false,
            reason: "使用者無權限執行操作：{$action} on {$resource}",
            code: 'INSUFFICIENT_PERMISSIONS',
            appliedRules: ['default_deny'],
        );
    }

    /**
     * 基於角色的授權檢查.
     *
     * @param string|null $userRole 使用者角色
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @return AuthorizationResult 授權結果
     */
    private function authorizeByRole(?string $userRole, string $resource, string $action): AuthorizationResult
    {
        if (empty($userRole)) {
            return new AuthorizationResult(false, '使用者角色為空', 'NO_ROLE');
        }

        $rolePermissions = $this->config['role_permissions'][$userRole] ?? [];

        // 檢查通配符權限
        if (in_array('*', $rolePermissions, true) || in_array("{$resource}.*", $rolePermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "角色 {$userRole} 擁有資源 {$resource} 的完整權限",
                code: 'ROLE_WILDCARD_ACCESS',
                appliedRules: ['role_wildcard'],
            );
        }

        // 檢查特定權限
        $requiredPermission = "{$resource}.{$action}";
        if (in_array($requiredPermission, $rolePermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "角色 {$userRole} 擁有權限 {$requiredPermission}",
                code: 'ROLE_SPECIFIC_ACCESS',
                appliedRules: ['role_specific'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "角色 {$userRole} 沒有權限 {$requiredPermission}",
            code: 'ROLE_INSUFFICIENT',
            appliedRules: ['role_check'],
        );
    }

    /**
     * 基於權限的授權檢查.
     *
     * @param array<string> $userPermissions 使用者權限
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @return AuthorizationResult 授權結果
     */
    private function authorizeByPermission(array $userPermissions, string $resource, string $action): AuthorizationResult
    {
        if (empty($userPermissions)) {
            return new AuthorizationResult(false, '使用者權限為空', 'NO_PERMISSIONS');
        }

        $requiredPermission = "{$resource}.{$action}";

        // 檢查通配符權限
        if (in_array('*', $userPermissions, true) || in_array("{$resource}.*", $userPermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者擁有資源 {$resource} 的完整權限",
                code: 'PERMISSION_WILDCARD_ACCESS',
                appliedRules: ['permission_wildcard'],
            );
        }

        // 檢查特定權限
        if (in_array($requiredPermission, $userPermissions, true)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者擁有權限 {$requiredPermission}",
                code: 'PERMISSION_SPECIFIC_ACCESS',
                appliedRules: ['permission_specific'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "使用者沒有權限 {$requiredPermission}",
            code: 'PERMISSION_INSUFFICIENT',
            appliedRules: ['permission_check'],
        );
    }

    /**
     * 基於屬性的授權檢查 (ABAC).
     *
     * @param int $userId 使用者 ID
     * @param string|null $userRole 使用者角色
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return AuthorizationResult 授權結果
     */
    private function authorizeByAttributes(
        int $userId,
        ?string $userRole,
        string $resource,
        string $action,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        // 時間基礎的存取控制
        $timeResult = $this->checkTimeBasedAccess($userRole, $action);
        if (!$timeResult->isAllowed() && $timeResult->getCode() !== 'TIME_CHECK_SKIPPED') {
            return $timeResult;
        }

        // IP 基礎的存取控制
        $ipResult = $this->checkIpBasedAccess($request, $userRole, $resource, $action);
        if (!$ipResult->isAllowed() && $ipResult->getCode() !== 'IP_CHECK_SKIPPED') {
            return $ipResult;
        }

        // 資源擁有者檢查
        if ($action === 'update' || $action === 'delete') {
            $ownershipResult = $this->checkResourceOwnership($userId, $resource, $request);
            if ($ownershipResult->isAllowed()) {
                return $ownershipResult;
            }
        }

        return new AuthorizationResult(false, '屬性檢查未通過', 'ATTRIBUTE_CHECK_FAILED');
    }

    /**
     * 自訂授權策略.
     *
     * @param int $userId 使用者 ID
     * @param string|null $userRole 使用者角色
     * @param array<string> $userPermissions 使用者權限
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return AuthorizationResult 授權結果
     */
    private function authorizeByCustomRules(
        int $userId,
        ?string $userRole,
        array $userPermissions,
        string $resource,
        string $action,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        $customRules = $this->config['custom_rules'] ?? [];

        foreach ($customRules as $ruleName => $ruleConfig) {
            if (!$this->matchesRuleConditions($ruleConfig['conditions'] ?? [], $resource, $action, $userRole)) {
                continue;
            }

            // 執行自訂規則邏輯
            $ruleResult = $this->executeCustomRule($ruleName, $ruleConfig, $userId, $userRole, $userPermissions, $request);
            if ($ruleResult !== null) {
                return $ruleResult;
            }
        }

        return new AuthorizationResult(false, '沒有適用的自訂規則', 'NO_CUSTOM_RULE');
    }

    /**
     * 檢查是否為超級管理員.
     *
     * @param string|null $userRole 使用者角色
     */
    private function isSuperAdmin(?string $userRole): bool
    {
        if (empty($userRole)) {
            return false;
        }

        return in_array($userRole, self::ADMIN_ROLES, true)
            || in_array($userRole, $this->config['admin_roles'] ?? [], true);
    }

    /**
     * 時間基礎的存取控制檢查.
     *
     * @param string|null $userRole 使用者角色
     * @param string $action 操作名稱
     * @return AuthorizationResult 授權結果
     */
    private function checkTimeBasedAccess(?string $userRole, string $action): AuthorizationResult
    {
        $timeRestrictions = $this->config['time_restrictions'] ?? [];
        if (empty($timeRestrictions)) {
            return new AuthorizationResult(true, '無時間限制', 'TIME_CHECK_SKIPPED');
        }

        $currentHour = (int) date('H');
        $currentDay = date('w'); // 0 (Sunday) to 6 (Saturday)

        foreach ($timeRestrictions as $restriction) {
            if (!$this->matchesTimeRestriction($restriction, $userRole, $action, $currentHour, $currentDay)) {
                continue;
            }

            return new AuthorizationResult(
                allowed: false,
                reason: "操作 {$action} 在當前時間不被允許",
                code: 'TIME_RESTRICTION_VIOLATED',
                appliedRules: ['time_restriction'],
            );
        }

        return new AuthorizationResult(true, '時間檢查通過', 'TIME_CHECK_PASSED');
    }

    /**
     * IP 基礎的存取控制檢查.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param string|null $userRole 使用者角色
     * @param string $resource 資源名稱
     * @param string $action 操作名稱
     * @return AuthorizationResult 授權結果
     */
    private function checkIpBasedAccess(
        ServerRequestInterface $request,
        ?string $userRole,
        string $resource,
        string $action,
    ): AuthorizationResult {
        $ipRestrictions = $this->config['ip_restrictions'] ?? [];
        if (empty($ipRestrictions)) {
            return new AuthorizationResult(true, '無 IP 限制', 'IP_CHECK_SKIPPED');
        }

        $clientIp = $this->getClientIpAddress($request);

        foreach ($ipRestrictions as $restriction) {
            if (!$this->matchesIpRestriction($restriction, $userRole, $resource, $action)) {
                continue;
            }

            $allowedIps = $restriction['allowed_ips'] ?? [];
            $blockedIps = $restriction['blocked_ips'] ?? [];

            // 檢查黑名單
            if (!empty($blockedIps) && $this->isIpInList($clientIp, $blockedIps)) {
                return new AuthorizationResult(
                    allowed: false,
                    reason: "IP 位址 {$clientIp} 被封鎖",
                    code: 'IP_BLOCKED',
                    appliedRules: ['ip_restriction'],
                );
            }

            // 檢查白名單
            if (!empty($allowedIps) && !$this->isIpInList($clientIp, $allowedIps)) {
                return new AuthorizationResult(
                    allowed: false,
                    reason: "IP 位址 {$clientIp} 不在允許清單中",
                    code: 'IP_NOT_ALLOWED',
                    appliedRules: ['ip_restriction'],
                );
            }
        }

        return new AuthorizationResult(true, 'IP 檢查通過', 'IP_CHECK_PASSED');
    }

    /**
     * 資源擁有者檢查.
     *
     * @param int $userId 使用者 ID
     * @param string $resource 資源名稱
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return AuthorizationResult 授權結果
     */
    private function checkResourceOwnership(
        int $userId,
        string $resource,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        // 從 URL 路徑或請求參數中提取資源 ID
        $resourceId = $this->extractResourceId($request, $resource);
        if ($resourceId === null) {
            return new AuthorizationResult(false, '無法識別資源 ID', 'RESOURCE_ID_NOT_FOUND');
        }

        // 檢查資源擁有者關係
        if ($this->isResourceOwner($userId, $resource, $resourceId)) {
            return new AuthorizationResult(
                allowed: true,
                reason: "使用者是資源 {$resource}#{$resourceId} 的擁有者",
                code: 'RESOURCE_OWNER_ACCESS',
                appliedRules: ['resource_ownership'],
            );
        }

        return new AuthorizationResult(
            allowed: false,
            reason: "使用者不是資源 {$resource}#{$resourceId} 的擁有者",
            code: 'NOT_RESOURCE_OWNER',
            appliedRules: ['resource_ownership'],
        );
    }

    /**
     * 從請求中提取資源名稱.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string 資源名稱
     */
    private function extractResource(ServerRequestInterface $request): string
    {
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        // 假設 API 路徑格式為 /api/v1/{resource}/{id?}
        if (count($segments) >= 3 && $segments[0] === 'api') {
            return $segments[2];
        }

        // 從路由屬性中提取（如果有設定）
        $routeResource = $request->getAttribute('route_resource');
        if ($routeResource !== null) {
            return $routeResource;
        }

        // 預設回傳 'unknown'
        return 'unknown';
    }

    /**
     * 從請求中提取操作名稱.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string 操作名稱
     */
    private function extractAction(ServerRequestInterface $request): string
    {
        $method = strtoupper($request->getMethod());
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        // 從路由屬性中提取（如果有設定）
        $routeAction = $request->getAttribute('route_action');
        if ($routeAction !== null) {
            return $routeAction;
        }

        // 根據 HTTP method 和路徑推斷操作
        $resourceId = $this->extractResourceIdFromPath($segments);

        return match ($method) {
            'GET' => $resourceId ? 'show' : 'index',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown'
        };
    }

    /**
     * 從請求中提取資源 ID.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param string $resource 資源名稱
     * @return int|null 資源 ID 或 null
     */
    private function extractResourceId(ServerRequestInterface $request, string $resource): ?int
    {
        $path = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $path);

        $resourceId = $this->extractResourceIdFromPath($segments);
        if ($resourceId !== null) {
            return $resourceId;
        }

        // 從請求參數中提取
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['id']) && is_numeric($queryParams['id'])) {
            return (int) $queryParams['id'];
        }

        // 從請求體中提取（用於 POST/PUT 請求）
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $body = $request->getParsedBody();
            if (is_array($body) && isset($body['id']) && is_numeric($body['id'])) {
                return (int) $body['id'];
            }
        }

        return null;
    }

    /**
     * 從路徑片段中提取資源 ID.
     *
     * @param array<string> $segments 路徑片段
     * @return int|null 資源 ID 或 null
     */
    private function extractResourceIdFromPath(array $segments): ?int
    {
        // 假設 API 路徑格式為 /api/v1/{resource}/{id}
        if (count($segments) >= 4 && $segments[0] === 'api' && is_numeric($segments[3])) {
            return (int) $segments[3];
        }

        // 尋找數字片段
        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                return (int) $segment;
            }
        }

        return null;
    }

    /**
     * 檢查使用者是否為資源擁有者.
     *
     * @param int $userId 使用者 ID
     * @param string $resource 資源名稱
     * @param int $resourceId 資源 ID
     */
    private function isResourceOwner(int $userId, string $resource, int $resourceId): bool
    {
        // 這裡應該查詢資料庫檢查擁有者關係
        // 為簡化示例，這裡使用配置或假設的邏輯

        $ownershipRules = $this->config['ownership_rules'] ?? [];

        // 如果沒有配置擁有者規則，預設允許
        if (empty($ownershipRules)) {
            return true;
        }

        // 檢查特定資源的擁有者規則
        if (isset($ownershipRules[$resource])) {
            // 這裡應該實作實際的資料庫查詢邏輯
            // 目前為示例用途，假設使用者 ID 和資源 ID 相等表示擁有者
            return $userId === $resourceId;
        }

        return false;
    }

    /**
     * 取得客戶端真實 IP 位址.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return string 客戶端 IP 位址
     */
    private function getClientIpAddress(ServerRequestInterface $request): string
    {
        // 檢查各種可能包含真實 IP 的標頭
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR',               // Standard
        ];

        $serverParams = $request->getServerParams();

        foreach ($headers as $header) {
            if (isset($serverParams[$header]) && !empty($serverParams[$header])) {
                $ip = trim(explode(',', $serverParams[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }

            if ($request->hasHeader($header)) {
                $ip = trim(explode(',', $request->getHeaderLine($header))[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // 預設回傳 localhost（適用於開發環境）
        return '127.0.0.1';
    }

    /**
     * 檢查 IP 是否在指定清單中.
     *
     * @param string $ip 要檢查的 IP 位址
     * @param array<string> $ipList IP 清單（支援 CIDR 格式）
     */
    private function isIpInList(string $ip, array $ipList): bool
    {
        foreach ($ipList as $ipPattern) {
            if ($this->ipMatches($ip, $ipPattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查 IP 是否匹配指定的模式.
     *
     * @param string $ip 要檢查的 IP 位址
     * @param string $pattern IP 模式（支援通配符和 CIDR）
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // 完全匹配
        if ($ip === $pattern) {
            return true;
        }

        // 通配符匹配
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';

            return preg_match($regex, $ip) === 1;
        }

        // CIDR 匹配
        if (str_contains($pattern, '/')) {
            [$subnet, $mask] = explode('/', $pattern);
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int) $mask);

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        return false;
    }

    /**
     * 檢查時間限制是否匹配.
     */
    private function matchesTimeRestriction(array $restriction, ?string $userRole, string $action, int $currentHour, int $currentDay): bool
    {
        // 檢查角色匹配
        if (isset($restriction['roles']) && !in_array($userRole, $restriction['roles'], true)) {
            return false;
        }

        // 檢查操作匹配
        if (isset($restriction['actions']) && !in_array($action, $restriction['actions'], true)) {
            return false;
        }

        // 檢查時間範圍
        if (isset($restriction['hours'])) {
            $allowedHours = $restriction['hours'];
            if (!in_array($currentHour, $allowedHours, true)) {
                return true; // 匹配限制（不允許的時間）
            }
        }

        // 檢查星期限制
        if (isset($restriction['days'])) {
            $allowedDays = $restriction['days'];
            if (!in_array($currentDay, $allowedDays, true)) {
                return true; // 匹配限制（不允許的日期）
            }
        }

        return false;
    }

    /**
     * 檢查 IP 限制是否匹配.
     */
    private function matchesIpRestriction(array $restriction, ?string $userRole, string $resource, string $action): bool
    {
        // 檢查角色匹配
        if (isset($restriction['roles']) && !in_array($userRole, $restriction['roles'], true)) {
            return false;
        }

        // 檢查資源匹配
        if (isset($restriction['resources']) && !in_array($resource, $restriction['resources'], true)) {
            return false;
        }

        // 檢查操作匹配
        if (isset($restriction['actions']) && !in_array($action, $restriction['actions'], true)) {
            return false;
        }

        return true;
    }

    /**
     * 檢查規則條件是否匹配.
     */
    private function matchesRuleConditions(array $conditions, string $resource, string $action, ?string $userRole): bool
    {
        foreach ($conditions as $key => $value) {
            $matches = match ($key) {
                'resource' => is_array($value)
                    ? in_array($resource, $value, true)
                    : (is_string($value) ? $resource === $value : true),
                'action' => is_array($value)
                    ? in_array($action, $value, true)
                    : (is_string($value) ? $action === $value : true),
                'role' => is_array($value)
                    ? in_array($userRole, $value, true)
                    : (is_string($value) ? $userRole === $value : true),
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * 執行自訂規則.
     */
    private function executeCustomRule(
        string $ruleName,
        array $ruleConfig,
        int $userId,
        ?string $userRole,
        array $userPermissions,
        ServerRequestInterface $request,
    ): ?AuthorizationResult {
        $ruleType = $ruleConfig['type'] ?? 'allow';
        $ruleMessage = $ruleConfig['message'] ?? "自訂規則 {$ruleName} 生效";

        return match ($ruleType) {
            'allow' => new AuthorizationResult(
                allowed: true,
                reason: $ruleMessage,
                code: 'CUSTOM_RULE_ALLOW',
                appliedRules: ["custom:{$ruleName}"],
            ),
            'deny' => new AuthorizationResult(
                allowed: false,
                reason: $ruleMessage,
                code: 'CUSTOM_RULE_DENY',
                appliedRules: ["custom:{$ruleName}"],
            ),
            'conditional' => $this->evaluateConditionalRule($ruleConfig, $userId, $userRole, $request),
            default => null,
        };
    }

    /**
     * 評估條件式規則.
     */
    private function evaluateConditionalRule(
        array $ruleConfig,
        int $userId,
        ?string $userRole,
        ServerRequestInterface $request,
    ): AuthorizationResult {
        // 這裡可以實作複雜的條件邏輯
        // 例如：時間、IP、請求參數、資料庫查詢等

        // 簡單示例：檢查請求參數
        $conditions = $ruleConfig['conditions'] ?? [];
        $requiredParams = $conditions['required_params'] ?? [];

        if (!empty($requiredParams)) {
            $queryParams = $request->getQueryParams();
            foreach ($requiredParams as $param) {
                if (!isset($queryParams[$param])) {
                    return new AuthorizationResult(
                        allowed: false,
                        reason: "缺少必要參數：{$param}",
                        code: 'MISSING_REQUIRED_PARAM',
                        appliedRules: ['conditional_rule'],
                    );
                }
            }
        }

        $result = $ruleConfig['result'] ?? 'allow';

        return new AuthorizationResult(
            allowed: $result === 'allow',
            reason: $ruleConfig['message'] ?? '條件式規則評估完成',
            code: $result === 'allow' ? 'CONDITIONAL_ALLOW' : 'CONDITIONAL_DENY',
            appliedRules: ['conditional_rule'],
        );
    }

    /**
     * 將授權資訊注入到請求中.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @param AuthorizationResult $result 授權結果
     * @return ServerRequestInterface 注入授權資訊後的請求物件
     */
    private function injectAuthorizationContext(
        ServerRequestInterface $request,
        AuthorizationResult $result,
    ): ServerRequestInterface {
        return $request
            ->withAttribute('authorization_result', $result)
            ->withAttribute('authorization_allowed', $result->isAllowed())
            ->withAttribute('authorization_reason', $result->getReason())
            ->withAttribute('authorization_code', $result->getCode())
            ->withAttribute('applied_rules', $result->getAppliedRules());
    }

    /**
     * 建立禁止存取的回應.
     *
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     * @return ResponseInterface HTTP 回應物件
     */
    private function createForbiddenResponse(string $message, string $code = 'FORBIDDEN'): ResponseInterface
    {
        $responseData = [
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c'),
        ];

        $body = json_encode($responseData, JSON_UNESCAPED_UNICODE);

        return new Response(
            status: 403,
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: $body,
        );
    }

    /**
     * 檢查是否應該處理此請求.
     *
     * @param ServerRequestInterface $request HTTP 請求物件
     * @return bool 是否應該處理
     */
    public function shouldProcess(ServerRequestInterface $request): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // 跳過不需要授權的路徑
        $skipPaths = array_merge(
            [
                '/auth/login',
                '/auth/register',
                '/auth/refresh',
                '/health',
                '/status',
                '/favicon.ico',
            ],
            $this->config['skip_paths'] ?? [],
        );

        $path = $request->getUri()->getPath();

        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return false;
            }
        }

        // 只處理需要授權的路徑
        $authPaths = $this->config['auth_paths'] ?? ['/api/'];

        foreach ($authPaths as $authPath) {
            if (str_starts_with($path, $authPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得預設配置.
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'default_policy' => self::DEFAULT_POLICY,
            'admin_roles' => self::ADMIN_ROLES,
            'skip_paths' => [],
            'auth_paths' => ['/api/'],
            'role_permissions' => [
                'admin' => ['*'],
                'moderator' => ['posts.*', 'comments.*'],
                'user' => ['posts.show', 'posts.create', 'comments.show', 'comments.create'],
                'guest' => ['posts.show', 'comments.show'],
            ],
            'ownership_rules' => [],
            'time_restrictions' => [],
            'ip_restrictions' => [],
            'custom_rules' => [],
        ];
    }

    /**
     * 取得中介軟體優先順序.
     *
     * @return int 優先順序（數值越小優先級越高）
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * 取得中介軟體名稱.
     *
     * @return string 中介軟體名稱
     */
    public function getName(): string
    {
        return self::MIDDLEWARE_NAME;
    }

    /**
     * 設定中介軟體優先順序.
     *
     * @param int $priority 優先順序
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * 設定中介軟體是否啟用.
     *
     * @param bool $enabled 是否啟用
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * 檢查中介軟體是否啟用.
     *
     * @return bool 是否啟用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 取得授權配置.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 設定授權配置.
     *
     * @param array<string, mixed> $config 配置陣列
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }
}

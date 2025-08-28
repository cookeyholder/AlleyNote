<?php

declare(strict_types=1);

namespace App\Domains\Security\DTOs;

use App\Domains\Security\Enums\ActivityType;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivitySeverity;
use DateTime;
use InvalidArgumentException;

/**
 * 活動記錄搜尋條件 DTO
 * 封裝搜尋活動記錄的各種條件參數
 */
class ActivityLogSearchDTO
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_SORT_BY = 'created_at';
    private const DEFAULT_SORT_ORDER = 'desc';

    public function __construct(
        private readonly ?int $userId = null,
        private readonly ?string $sessionId = null,
        private readonly ?ActivityType $actionType = null,
        private readonly ?ActivityCategory $actionCategory = null,
        private readonly ?ActivityStatus $status = null,
        private readonly ?ActivitySeverity $minSeverity = null,
        private readonly ?string $targetType = null,
        private readonly ?string $targetId = null,
        private readonly ?string $ipAddress = null,
        private readonly ?DateTime $startDate = null,
        private readonly ?DateTime $endDate = null,
        private readonly ?string $searchKeyword = null,
        private readonly int $page = self::DEFAULT_PAGE,
        private readonly int $perPage = self::DEFAULT_PER_PAGE,
        private readonly string $sortBy = self::DEFAULT_SORT_BY,
        private readonly string $sortOrder = self::DEFAULT_SORT_ORDER
    ) {
        $this->validateSearchConditions();
    }

    /**
     * 建立搜尋 DTO 的 Builder
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 設定使用者 ID
     */
    public function withUserId(int $userId): self
    {
        return new self(
            userId: $userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定 Session ID
     */
    public function withSessionId(string $sessionId): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定行為類型
     */
    public function withActionType(ActivityType $actionType): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定行為分類
     */
    public function withActionCategory(ActivityCategory $actionCategory): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定狀態
     */
    public function withStatus(ActivityStatus $status): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定最小嚴重程度
     */
    public function withMinSeverity(ActivitySeverity $minSeverity): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定目標類型和 ID
     */
    public function withTarget(string $targetType, string $targetId): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $targetType,
            targetId: $targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定 IP 位址
     */
    public function withIpAddress(string $ipAddress): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定時間範圍
     */
    public function withTimeRange(DateTime $startDate, DateTime $endDate): self
    {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('開始時間不能大於結束時間');
        }

        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $startDate,
            endDate: $endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定搜尋關鍵字
     */
    public function withSearchKeyword(string $searchKeyword): self
    {
        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定分頁參數
     */
    public function withPagination(int $page, int $perPage): self
    {
        if ($page < 1) {
            throw new InvalidArgumentException('頁碼必須大於 0');
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('每頁筆數必須介於 1 到 ' . self::MAX_PER_PAGE . ' 之間');
        }

        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $page,
            perPage: $perPage,
            sortBy: $this->sortBy,
            sortOrder: $this->sortOrder
        );
    }

    /**
     * 設定排序參數
     */
    public function withSort(string $sortBy, string $sortOrder = 'desc'): self
    {
        $validSortFields = [
            'created_at',
            'occurred_at',
            'user_id',
            'action_type',
            'action_category',
            'status',
            'ip_address'
        ];

        if (!in_array($sortBy, $validSortFields, true)) {
            throw new InvalidArgumentException('不支援的排序欄位：' . $sortBy);
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('排序順序必須是 asc 或 desc');
        }

        return new self(
            userId: $this->userId,
            sessionId: $this->sessionId,
            actionType: $this->actionType,
            actionCategory: $this->actionCategory,
            status: $this->status,
            minSeverity: $this->minSeverity,
            targetType: $this->targetType,
            targetId: $this->targetId,
            ipAddress: $this->ipAddress,
            startDate: $this->startDate,
            endDate: $this->endDate,
            searchKeyword: $this->searchKeyword,
            page: $this->page,
            perPage: $this->perPage,
            sortBy: $sortBy,
            sortOrder: $sortOrder
        );
    }

    // Getters
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
    public function getActionType(): ?ActivityType
    {
        return $this->actionType;
    }
    public function getActionCategory(): ?ActivityCategory
    {
        return $this->actionCategory;
    }
    public function getStatus(): ?ActivityStatus
    {
        return $this->status;
    }
    public function getMinSeverity(): ?ActivitySeverity
    {
        return $this->minSeverity;
    }
    public function getTargetType(): ?string
    {
        return $this->targetType;
    }
    public function getTargetId(): ?string
    {
        return $this->targetId;
    }
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }
    public function getSearchKeyword(): ?string
    {
        return $this->searchKeyword;
    }
    public function getPage(): int
    {
        return $this->page;
    }
    public function getPerPage(): int
    {
        return $this->perPage;
    }
    public function getSortBy(): string
    {
        return $this->sortBy;
    }
    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    /**
     * 取得查詢偏移量
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * 判斷是否有搜尋條件
     */
    public function hasFilters(): bool
    {
        return $this->userId !== null
            || $this->sessionId !== null
            || $this->actionType !== null
            || $this->actionCategory !== null
            || $this->status !== null
            || $this->minSeverity !== null
            || $this->targetType !== null
            || $this->targetId !== null
            || $this->ipAddress !== null
            || $this->startDate !== null
            || $this->endDate !== null
            || $this->searchKeyword !== null;
    }

    /**
     * 轉換為陣列格式
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'action_type' => $this->actionType?->value,
            'action_category' => $this->actionCategory?->value,
            'status' => $this->status?->value,
            'min_severity' => $this->minSeverity?->value,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'ip_address' => $this->ipAddress,
            'start_date' => $this->startDate?->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate?->format('Y-m-d H:i:s'),
            'search_keyword' => $this->searchKeyword,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'sort_by' => $this->sortBy,
            'sort_order' => $this->sortOrder,
        ];
    }

    /**
     * 驗證搜尋條件
     */
    private function validateSearchConditions(): void
    {
        if ($this->startDate && $this->endDate && $this->startDate > $this->endDate) {
            throw new InvalidArgumentException('開始時間不能大於結束時間');
        }

        if ($this->page < 1) {
            throw new InvalidArgumentException('頁碼必須大於 0');
        }

        if ($this->perPage < 1 || $this->perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException('每頁筆數必須介於 1 到 ' . self::MAX_PER_PAGE . ' 之間');
        }
    }
}

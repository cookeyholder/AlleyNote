#!/bin/bash

# 批量修復 array 型別定義問題的腳本

cd /Users/cookeyholder/Projects/AlleyNote/backend

# 找出需要修復 'no value type specified in iterable type array' 錯誤的檔案
files_to_fix=(
    "app/Application/Services/Statistics/StatisticsApplicationService.php"
    "app/Application/Services/Statistics/StatisticsQueryService.php"
    "app/Application/DTOs/Statistics/PostStatisticsDTO.php"
    "app/Application/DTOs/Statistics/SourceDistributionDTO.php"
    "app/Application/DTOs/Statistics/StatisticsOverviewDTO.php"
    "app/Application/DTOs/Statistics/UserActivityDTO.php"
    "app/Application/Middleware/AuthorizationResult.php"
    "app/Application/Middleware/JwtAuthorizationMiddleware.php"
    "app/Application/Middleware/RateLimitMiddleware.php"
    "app/Application/Controllers/Security/CSPReportController.php"
)

echo "開始修復 array 型別定義問題..."

# 針對每個檔案修復常見的 array 型別問題
for file in "${files_to_fix[@]}"; do
    if [[ -f "$file" ]]; then
        echo "正在處理檔案: $file"

        # 修復 array 參數型別
        sed -i '' 's/public function \([a-zA-Z_][a-zA-Z0-9_]*\)(.*array \$\([a-zA-Z_][a-zA-Z0-9_]*\)/public function \1(array<string, mixed> $\2/g' "$file"

        # 修復 return array 型別
        sed -i '' 's/): array$/): array<string, mixed>/g' "$file"

        echo "已處理檔案: $file"
    else
        echo "檔案不存在: $file"
    fi
done

echo "批量修復完成！"

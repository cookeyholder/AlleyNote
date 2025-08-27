#!/bin/bash

# 清理 PHPStan 抑制註解的腳本

echo "開始清理無用的 PHPStan 抑制註解..."

# 所有需要清理的檔案及行號
declare -A files_lines=(
    ["tests/Integration/Http/PostControllerTest.php"]="131"
    ["tests/Integration/JwtAuthenticationIntegrationTest.php"]="388 416 470"
    ["tests/Integration/JwtAuthenticationIntegrationTest_simple.php"]="272"
    ["tests/Integration/JwtTokenBlacklistIntegrationTest.php"]="339"
    ["tests/Integration/PostControllerTest.php"]="525 552"
    ["tests/Integration/Repositories/PostRepositoryTest.php"]="91 167"
    ["tests/TestCase.php"]="229"
    ["tests/Unit/DTOs/BaseDTOTest.php"]="46"
    ["tests/Unit/Domains/Auth/Exceptions/JwtExceptionTest.php"]="23"
    ["tests/Unit/Domains/Auth/Services/AuthenticationServiceTest.php"]="732 750"
    ["tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php"]="1249"
    ["tests/Unit/Services/AttachmentServiceTest.php"]="233"
    ["tests/Unit/Services/Security/FileSecurityServiceTest.php"]="252"
)

for file in "${!files_lines[@]}"; do
    if [ -f "$file" ]; then
        echo "處理檔案: $file"
        lines=${files_lines[$file]}
        
        # 對每個行號進行處理
        for line_num in $lines; do
            # 使用 sed 移除包含 @phpstan-ignore 的行
            sed -i "${line_num}s/.*@phpstan-ignore.*//g" "$file"
            # 移除空行
            sed -i "${line_num}{/^[[:space:]]*$/d;}" "$file"
            echo "  - 清理第 $line_num 行"
        done
    else
        echo "檔案不存在: $file"
    fi
done

echo "PHPStan 抑制註解清理完成！"
#!/bin/bash

# 修復被批量處理破壞的檔案
PROJECT_ROOT="/Users/cookeyholder/Projects/AlleyNote"
cd "$PROJECT_ROOT"

echo "正在檢查和恢復損壞的檔案..."

# 取得最近修改的 PHP 檔案列表
git diff --name-only HEAD~3 | grep '\.php$' | while read -r file; do
    if [[ -f "$file" ]]; then
        # 檢查檔案第一行是否包含過多內容（格式問題的指標）
        first_line=$(head -1 "$file")
        if [[ "$first_line" == *"declare(strict_types=1);"* && "$first_line" == *"namespace"* ]]; then
            echo "發現格式問題: $file"

            # 嘗試從 git 歷史恢復
            if git show "HEAD~3:$file" > "/tmp/$(basename "$file").backup" 2>/dev/null; then
                # 檢查備份檔案的第一行是否正常
                backup_first_line=$(head -1 "/tmp/$(basename "$file").backup")
                if [[ "$backup_first_line" == "<?php" && "$backup_first_line" != *"declare"* ]]; then
                    echo "✅ 恢復: $file"
                    cp "/tmp/$(basename "$file").backup" "$file"
                else
                    echo "⚠️  備份版本也有問題，跳過: $file"
                fi
            else
                echo "❌ 無法從 git 歷史恢復: $file"
            fi
        fi
    fi
done

echo "檢查語法錯誤..."
error_count=0

git diff --name-only HEAD~3 | grep '\.php$' | while read -r file; do
    if [[ -f "$file" ]]; then
        if ! php -l "$file" >/dev/null 2>&1; then
            echo "❌ 語法錯誤: $file"
            ((error_count++))
        fi
    fi
done

if [[ $error_count -eq 0 ]]; then
    echo "✅ 所有檔案語法檢查通過"
else
    echo "❌ 發現 $error_count 個檔案有語法錯誤"
fi

echo "恢復完成"

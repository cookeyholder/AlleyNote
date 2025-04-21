#!/bin/bash

# 輸出檔案名稱
OUTPUT_FILE="project_snapshot.md"
# 目錄樹顯示深度
MAX_DEPTH=4

echo "# 專案快照 ($(date))" >"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"
echo "## 目錄結構 (最大深度: $MAX_DEPTH)" >>"$OUTPUT_FILE"
echo '```' >>"$OUTPUT_FILE"
# 檢查 tree 指令是否存在
if command -v tree &>/dev/null; then
    tree -L $MAX_DEPTH -d -I 'vendor|storage|.git|node_modules|docker' >>"$OUTPUT_FILE"
else
    echo "警告: 'tree' 指令未安裝，無法顯示目錄樹。" >>"$OUTPUT_FILE"
    find . -maxdepth $MAX_DEPTH -type d -not \( -path ./vendor -prune \) -not \( -path ./storage -prune \) -not \( -path ./.git -prune \) -not \( -path ./node_modules -prune \) -not \( -path ./docker -prune \) -print >>"$OUTPUT_FILE"
fi
echo '```' >>"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"

echo "## 原始碼 ($(src/))" >>"$OUTPUT_FILE"
echo '```plaintext' >>"$OUTPUT_FILE"
find src -name '*.php' | while read -r file; do
    echo "- $file"
    # 提取 class/interface/trait 名稱
    grep -E '^(abstract |final )?class |^interface |^trait ' "$file" | sed -E 's/^(abstract |final )?class |^interface |^trait //; s/\{?//; s/ .*//' | awk '{print "  - 定義: " $0}' >>"$OUTPUT_FILE"
    # 提取 public function 名稱
    grep -E '^\s*public function ' "$file" | sed -E 's/^\s*public function //; s/\(.*//' | awk '{print "  - 方法: " $0 "()"}' >>"$OUTPUT_FILE"
done
echo '```' >>"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"

echo "## Helper 函式 ($(src/Helpers/functions.php))" >>"$OUTPUT_FILE"
if [ -f "src/Helpers/functions.php" ]; then
    echo '```plaintext' >>"$OUTPUT_FILE"
    grep -E '^function ' src/Helpers/functions.php | sed -E 's/^function //; s/\(.*//' | awk '{print "- " $0 "()"}' >>"$OUTPUT_FILE"
    echo '```' >>"$OUTPUT_FILE"
else
    echo "Helper 檔案未找到。" >>"$OUTPUT_FILE"
fi
echo "" >>"$OUTPUT_FILE"

echo "## 資料庫遷移 ($(database/migrations/))" >>"$OUTPUT_FILE"
echo '```plaintext' >>"$OUTPUT_FILE"
ls database/migrations/*.php 2>/dev/null | xargs -n 1 basename || echo "找不到遷移檔案。" >>"$OUTPUT_FILE"
echo '```' >>"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"

echo "## 視圖 ($(resources/views/))" >>"$OUTPUT_FILE"
echo '```plaintext' >>"$OUTPUT_FILE"
find resources/views -type f \( -name '*.php' -o -name '*.blade.php' \) 2>/dev/null || echo "找不到視圖檔案。" >>"$OUTPUT_FILE"
echo '```' >>"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"

echo "## 測試 ($(tests/))" >>"$OUTPUT_FILE"
echo '```plaintext' >>"$OUTPUT_FILE"
find tests -name '*Test.php' 2>/dev/null || echo "找不到測試檔案。" >>"$OUTPUT_FILE"
echo '```' >>"$OUTPUT_FILE"
echo "" >>"$OUTPUT_FILE"

echo "## 關鍵設定檔" >>"$OUTPUT_FILE"
echo '```plaintext' >>"$OUTPUT_FILE"
echo "- composer.json" >>"$OUTPUT_FILE"
echo "- env.example" >>"$OUTPUT_FILE"
echo "- docker-compose.yml" >>"$OUTPUT_FILE"
echo "- phpunit.xml" >>"$OUTPUT_FILE"
echo '```' >>"$OUTPUT_FILE"

echo "快照已產生: $OUTPUT_FILE"

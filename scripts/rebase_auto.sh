#!/bin/bash
# scripts/rebase_auto.sh

while true; do
    # 處理 frontend/package.json 衝突
    if [ -f "frontend/package.json" ] && grep -q "<<<<<<< HEAD" frontend/package.json; then
        echo "正在解決 frontend/package.json 衝突..."
        python3 scripts/merge_package_json.py frontend/package.json
        git add frontend/package.json
    fi

    # 處理根目錄 package.json 衝突
    if [ -f "package.json" ] && grep -q "<<<<<<< HEAD" package.json; then
        echo "正在解決 package.json 衝突..."
        python3 scripts/merge_package_json.py package.json
        git add package.json
    fi

    # 處理 package-lock.json 衝突 (優先採納 main 分支的版本)
    if [ -f "package-lock.json" ] && grep -q "<<<<<<< HEAD" package-lock.json; then
        echo "正在解決 package-lock.json 衝突 (採納 theirs)..."
        git checkout --theirs package-lock.json
        git add package-lock.json
    fi

    # 移除多餘的 frontend/package-lock.json
    if [ -f "frontend/package-lock.json" ]; then
        rm -f frontend/package-lock.json
        git rm --cached frontend/package-lock.json >/dev/null 2>&1
        git add frontend/package-lock.json >/dev/null 2>&1
    fi

    # 檢查是否還有未解決的衝突
    UNMERGED=$(git status --short | grep "^UU " || true)
    if [ -n "$UNMERGED" ]; then
        echo "發現無法自動處理的衝突，請手動介入後再執行此腳本："
        echo "$UNMERGED"
        exit 1
    fi

    echo "繼續 Rebase..."
    GIT_EDITOR=true git rebase --continue
    
    # 檢查 Rebase 是否完成
    if [ $? -eq 0 ]; then
        echo "Rebase 成功完成！"
        exit 0
    fi
done

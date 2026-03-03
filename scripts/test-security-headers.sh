#!/bin/bash

# HTTP 安全標頭測試腳本
# 此腳本用於測試 AlleyNote 專案的安全標頭配置

set -e

echo "========================================="
echo "AlleyNote HTTP 安全標頭測試"
echo "========================================="
echo ""

# 顏色定義
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 測試函數
test_header() {
    local url=$1
    local header_name=$2
    local expected_pattern=$3
    local description=$4
    
    result=$(curl -sI "$url" | grep -i "^$header_name:" || echo "")
    
    if [ -z "$result" ]; then
        echo -e "${RED}✗${NC} $description - ${YELLOW}標頭不存在${NC}"
        return 1
    elif echo "$result" | grep -qiE "$expected_pattern"; then
        echo -e "${GREEN}✓${NC} $description"
        echo -e "  ${YELLOW}→${NC} $result"
        return 0
    else
        echo -e "${RED}✗${NC} $description - ${YELLOW}值不符合預期${NC}"
        echo -e "  ${YELLOW}→${NC} $result"
        return 1
    fi
}

test_header_not_exists() {
    local url=$1
    local header_name=$2
    local description=$3
    
    result=$(curl -sI "$url" | grep -i "^$header_name:" || echo "")
    
    if [ -z "$result" ]; then
        echo -e "${GREEN}✓${NC} $description - ${GREEN}標頭已移除${NC}"
        return 0
    else
        echo -e "${RED}✗${NC} $description - ${RED}標頭仍存在${NC}"
        echo -e "  ${YELLOW}→${NC} $result"
        return 1
    fi
}

# 測試前端安全標頭
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. 前端安全標頭測試 (http://localhost:3000)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

test_header "http://localhost:3000" "X-Frame-Options" "SAMEORIGIN" "X-Frame-Options"
test_header "http://localhost:3000" "X-Content-Type-Options" "nosniff" "X-Content-Type-Options"
test_header "http://localhost:3000" "X-XSS-Protection" "1; mode=block" "X-XSS-Protection"
test_header "http://localhost:3000" "Referrer-Policy" "strict-origin-when-cross-origin" "Referrer-Policy"
test_header "http://localhost:3000" "Cross-Origin-Opener-Policy" "same-origin" "Cross-Origin-Opener-Policy"
test_header "http://localhost:3000" "Cross-Origin-Resource-Policy" "same-origin" "Cross-Origin-Resource-Policy"
test_header "http://localhost:3000" "Permissions-Policy" "geolocation=.*microphone=.*camera=" "Permissions-Policy"
test_header "http://localhost:3000" "Content-Security-Policy" "default-src.*script-src.*style-src" "Content-Security-Policy"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. API 安全標頭測試 (http://localhost:8080)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

test_header "http://localhost:8080/api/health" "X-Frame-Options" "DENY" "X-Frame-Options"
test_header "http://localhost:8080/api/health" "X-Content-Type-Options" "nosniff" "X-Content-Type-Options"
test_header "http://localhost:8080/api/health" "X-XSS-Protection" "1; mode=block" "X-XSS-Protection"
test_header "http://localhost:8080/api/health" "Referrer-Policy" "strict-origin-when-cross-origin" "Referrer-Policy"
test_header "http://localhost:8080/api/health" "Access-Control-Allow-Origin" "http://localhost:3000" "CORS - Allow-Origin"
test_header_not_exists "http://localhost:8080/api/health" "X-Powered-By" "X-Powered-By (應該移除)"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3. 伺服器資訊隱藏測試"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

test_header_not_exists "http://localhost:3000" "Server" "Server 版本資訊 (前端)"
test_header_not_exists "http://localhost:8080/api/health" "Server" "Server 版本資訊 (API)"
test_header_not_exists "http://localhost:8080/api/health" "X-Powered-By" "X-Powered-By (PHP 版本)"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "4. CSP 詳細內容檢查"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

csp=$(curl -sI http://localhost:3000 | grep -i "Content-Security-Policy:" | sed 's/Content-Security-Policy: //')
echo "CSP 內容："
echo "$csp" | tr ';' '\n' | sed 's/^/  • /'

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "測試完成"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "建議："
echo "  1. 在生產環境中，X-Frame-Options 應設為 DENY"
echo "  2. 在生產環境中，移除 CSP 的 'unsafe-inline' 和 'unsafe-eval'"
echo "  3. 在生產環境中，啟用 HSTS (Strict-Transport-Security)"
echo "  4. 定期使用 https://securityheaders.com/ 進行線上測試"
echo ""

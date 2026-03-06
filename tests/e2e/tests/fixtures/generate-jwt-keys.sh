#!/bin/bash

set -e

ROOT_DIR="$(cd "$(dirname "$0")/../../../.." && pwd)"
KEY_DIR="$ROOT_DIR/backend/keys"
PRIVATE_KEY="$KEY_DIR/private.pem"
PUBLIC_KEY="$KEY_DIR/public.pem"

mkdir -p "$KEY_DIR"

if [ -f "$PRIVATE_KEY" ] && [ -f "$PUBLIC_KEY" ]; then
  exit 0
fi

openssl genrsa -out "$PRIVATE_KEY" 2048 > /dev/null 2>&1
openssl rsa -in "$PRIVATE_KEY" -pubout -out "$PUBLIC_KEY" > /dev/null 2>&1
chmod 600 "$PRIVATE_KEY"
chmod 644 "$PUBLIC_KEY"

echo "JWT 測試金鑰已建立於 backend/keys"

#!/usr/bin/env bash

set -euo pipefail

workspace_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
log_dir="${workspace_root}/.devcontainer/.logs"
mkdir -p "${log_dir}"

start_if_not_listening() {
    local service_name="$1"
    local port="$2"
    shift 2

    if lsof -iTCP:"${port}" -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "[devcontainer] ${service_name} already running on :${port}, skip"
        return 0
    fi

    echo "[devcontainer] starting ${service_name} on :${port}"
    nohup "$@" >"${log_dir}/${service_name}.log" 2>&1 &
}

start_if_not_listening \
    "backend" \
    "8081" \
    php -S 0.0.0.0:8081 -t "${workspace_root}/backend/public" "${workspace_root}/backend/public/index.php"

start_if_not_listening \
    "frontend" \
    "3000" \
    npx live-server "${workspace_root}/frontend" --host=0.0.0.0 --port=3000 --no-browser --quiet --watch="${workspace_root}/frontend" --proxy=/api:http://127.0.0.1:8081/api

echo "[devcontainer] services ready: frontend=http://localhost:3000 backend=http://localhost:8081"

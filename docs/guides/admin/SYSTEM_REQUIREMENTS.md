# AlleyNote 系統需求和環境說明

> 📋 **用途**：為系統管理員提供完整的硬體、軟體需求和環境準備指南

**版本**: v4.2
**更新日期**: 2025-09-27
**適用版本**: PHP 8.4.12 + Docker 28.3.3 + Vite 5

---

## 🖥️ 硬體需求

### 最低需求 (開發環境)
- **CPU**：2 核心 (2.5 GHz)
- **記憶體**：4 GB RAM
- **硬碟空間**：20 GB 可用空間
- **網路**：100 Mbps 網路連線

### 建議需求 (測試環境)
- **CPU**：4 核心 (3.0 GHz)
- **記憶體**：8 GB RAM
- **硬碟空間**：50 GB 可用空間（SSD 建議）
- **網路**：1 Gbps 網路連線

### 生產環境需求
- **CPU**：8 核心 (3.5 GHz)
- **記憶體**：16 GB RAM
- **硬碟空間**：100 GB 可用空間（NVMe SSD 必須）
- **網路**：1 Gbps 網路連線
- **備援**：RAID 1 或以上等級
- **負載平衡**：支援多節點部署

---

## 💻 軟體需求

### 🐳 容器化環境 (推薦)
- **Docker**: 28.3.3 或更新版本
- **Docker Compose**: v2.39.2 或更新版本
- **前端技術**: Vite 5 + TypeScript + Axios + Tailwind CSS
- **後端架構**: PHP 8.4.12 DDD 分層架構

### 🗄️ 資料庫系統
#### 支援的資料庫（優先順序）
1. **SQLite3** （強烈推薦，預設選項）
   - 輕量級、無需額外配置
   - 適合小到中型應用
   - 內建於 PHP，零維護成本

2. **PostgreSQL 16+** （大型部署推薦）
   - 高效能、高可靠性
   - 支援複雜查詢和進階功能
   - 適合大型應用和高併發環境

### 作業系統
#### 支援的 Linux 發行版
- **Debian 12** （強烈推薦）
- **Debian 11** （推薦）
- **Ubuntu 24.04 LTS**
- **Ubuntu 22.04 LTS**
- **Rocky Linux 9**
- **AlmaLinux 9**
- **RHEL 9**

#### Windows 支援
- **Windows Server 2022**
- **Windows Server 2019**
- **Windows 11 Pro**（開發環境）
- **Windows 10 Pro**（開發環境）

### 必要軟體版本
```bash
# Docker
Docker Engine 28.3.3 或更新版本
Docker Compose v2.39.2 或更新版本

# Git
Git 2.40.0 或更新版本

# Node.js (前端開發)
Node.js 18.0+ LTS (建議 18.19+)
npm 9.0+ 或更新版本
pnpm 8.0+ (可選，更快的套件管理)

# 系統工具
curl, wget, unzip, tar, jq
```

### 檢查系統需求腳本
```bash
#!/bin/bash
echo "=== AlleyNote v4.2 系統需求檢查 ==="

# 檢查作業系統
echo "1. 作業系統："
uname -a

# 檢查 CPU 核心數
echo "2. CPU 核心數："
nproc

# 檢查記憶體
echo "3. 記憶體："
free -h

# 檢查硬碟空間
echo "4. 硬碟空間："
df -h

# 檢查 Docker
echo "5. Docker 版本："
docker --version 2>/dev/null || echo "Docker 未安裝"

# 檢查 Docker Compose
echo "6. Docker Compose 版本："
docker compose version 2>/dev/null || echo "Docker Compose 未安裝"

# 檢查 Git
echo "7. Git 版本："
git --version 2>/dev/null || echo "Git 未安裝"

# 檢查 Node.js（開發環境）
echo "8. Node.js 版本："
node --version 2>/dev/null || echo "Node.js 未安裝（前端開發需要）"

# 檢查 npm
echo "9. npm 版本："
npm --version 2>/dev/null || echo "npm 未安裝（前端開發需要）"

# 檢查網路連線
echo "10. 網路連線："
curl -s --max-time 5 https://github.com > /dev/null && echo "網路連線正常" || echo "網路連線異常"

echo "=== 檢查完成 ==="
```

### 快速環境驗證
```bash
# 下載並執行檢查腳本
curl -sSL https://raw.githubusercontent.com/your-org/alleynote/main/scripts/check-requirements.sh | bash

# 或手動檢查關鍵需求
docker --version && docker compose version && echo "✅ Docker 環境就緒"
```

# 檢查硬碟空間
echo "4. 硬碟空間："
df -h /

# 檢查 Docker
echo "5. Docker 版本："
docker --version 2>/dev/null || echo "❌ Docker 未安裝"

# 檢查 Docker Compose
echo "6. Docker Compose 版本："
docker compose --version 2>/dev/null || echo "❌ Docker Compose 未安裝"

# 檢查 Git
echo "7. Git 版本："
git --version 2>/dev/null || echo "❌ Git 未安裝"

# 檢查 Node.js (前端開發需要)
echo "8. Node.js 版本："
node --version 2>/dev/null || echo "⚠️ Node.js 未安裝 (前端開發需要)"

# 檢查 npm
echo "9. npm 版本："
npm --version 2>/dev/null || echo "⚠️ npm 未安裝 (前端開發需要)"

echo "=== 檢查完成 ==="
```

---

## 🌐 網路需求

### 端口需求
| 端口 | 協定 | 用途 | 必要性 |
|------|------|------|--------|
| 80 | HTTP | Web 服務 | 必要 |
| 443 | HTTPS | SSL Web 服務 | 建議 |
| 22 | SSH | 遠端管理 | 建議 |
| 6379 | TCP | Redis 快取 | 內部使用 |

### 防火牆設定
#### Ubuntu/Debian (UFW)
```bash
# 允許 SSH
ufw allow 22

# 允許 HTTP
ufw allow 80

# 允許 HTTPS
ufw allow 443

# 啟用防火牆
ufw enable

# 檢查狀態
ufw status
```

#### CentOS/Rocky Linux (firewalld)
```bash
# 允許 HTTP 和 HTTPS
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-service=ssh

# 重新載入設定
firewall-cmd --reload

# 檢查狀態
firewall-cmd --list-all
```

### 網路連線測試
```bash
# 測試網路連線
ping -c 4 8.8.8.8

# 測試 DNS 解析
nslookup google.com

# 測試 HTTPS 連線
curl -I https://github.com

# 檢查端口佔用
netstat -tulpn | grep :80
netstat -tulpn | grep :443
```

---

## 🏗️ 環境準備

### 1. 系統更新
#### Ubuntu/Debian
```bash
# 更新套件列表
sudo apt update

# 升級系統套件
sudo apt upgrade -y

# 安裝必要工具
sudo apt install -y curl wget git unzip software-properties-common
```

#### CentOS/Rocky Linux
```bash
# 更新系統
sudo yum update -y

# 安裝必要工具
sudo yum install -y curl wget git unzip yum-utils
```

### 2. Docker 安裝
#### Ubuntu/Debian
```bash
# 安裝 Docker 官方 GPG 金鑰
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# 新增 Docker 官方 APT 源
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# 更新套件列表並安裝 Docker
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io

# 安裝 Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker compose
sudo chmod +x /usr/local/bin/docker compose

# 將使用者加入 docker 群組
sudo usermod -aG docker $USER

# 啟用並啟動 Docker 服務
sudo systemctl enable docker
sudo systemctl start docker
```

#### CentOS/Rocky Linux
```bash
# 安裝 Docker
sudo yum install -y yum-utils
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
sudo yum install -y docker-ce docker-ce-cli containerd.io

# 安裝 Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker compose
sudo chmod +x /usr/local/bin/docker compose

# 將使用者加入 docker 群組
sudo usermod -aG docker $USER

# 啟用並啟動 Docker 服務
sudo systemctl enable docker
sudo systemctl start docker
```

### 3. 驗證安裝
```bash
# 重新登入以使群組變更生效
newgrp docker

# 測試 Docker
docker run hello-world

# 測試 Docker Compose
docker compose --version

# 檢查 Docker 服務狀態
systemctl status docker
```

---

## 💾 儲存需求

### 目錄結構
```
/var/alleynote/              # 主要安裝目錄
├── database/                # SQLite 資料庫檔案
│   ├── alleynote.sqlite3        # 主要資料庫
│   └── backups/            # 資料庫備份
├── storage/                 # 應用程式儲存
│   ├── uploads/            # 上傳檔案
│   ├── cache/              # 快取檔案
│   └── logs/               # 日誌檔案
├── ssl-data/               # SSL 憑證
└── certbot-data/           # Certbot 資料
```

### 磁碟空間規劃
| 目錄 | 預估使用量 | 建議空間 | 說明 |
|------|------------|----------|------|
| `/var/alleynote` | 1-2 GB | 10 GB | 主要程式目錄 |
| `database/` | 100-500 MB | 5 GB | 資料庫及備份 |
| `storage/uploads/` | 視使用情況 | 20 GB | 使用者上傳檔案 |
| `storage/logs/` | 50-200 MB | 2 GB | 系統日誌 |
| `ssl-data/` | 10 MB | 100 MB | SSL 憑證 |

### 備份空間需求
- **每日備份**：約 100-500 MB
- **保留 30 天**：約 3-15 GB
- **建議備份空間**：20 GB 以上

---

## 🔒 安全考量

### SELinux 設定（CentOS/Rocky Linux）
```bash
# 檢查 SELinux 狀態
getenforce

# 臨時停用 SELinux（測試用）
sudo setenforce 0

# 永久停用 SELinux（編輯 /etc/selinux/config）
sudo sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config

# 或設定為 permissive 模式
sudo sed -i 's/SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config
```

### 檔案權限設定
```bash
# 建立專用使用者
sudo useradd -r -s /bin/false alleynote

# 設定目錄權限
sudo mkdir -p /var/alleynote
sudo chown -R alleynote:alleynote /var/alleynote
sudo chmod -R 755 /var/alleynote

# 設定資料庫目錄權限
sudo chmod 700 /var/alleynote/database
sudo chmod 600 /var/alleynote/database/*.db
```

### 系統安全加固
```bash
# 更新系統套件
sudo apt update && sudo apt upgrade -y

# 安裝 fail2ban 防護暴力破解
sudo apt install -y fail2ban

# 設定 SSH 安全
sudo sed -i 's/#PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo systemctl restart ssh

# 設定自動安全更新
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## 🧪 效能調校

### 系統參數調整
```bash
# 編輯 /etc/sysctl.conf
sudo tee -a /etc/sysctl.conf << EOF
# 網路效能調校
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 87380 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216

# 檔案系統效能調校
fs.file-max = 65535
vm.swappiness = 10
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5
EOF

# 套用設定
sudo sysctl -p
```

### Docker 效能調校
```bash
# 編輯 /etc/docker/daemon.json
sudo tee /etc/docker/daemon.json << EOF
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "storage-driver": "overlay2",
  "storage-opts": [
    "overlay2.override_kernel_check=true"
  ]
}
EOF

# 重啟 Docker 服務
sudo systemctl restart docker
```

---

## 📊 監控需求

### 系統監控工具
```bash
# 安裝基本監控工具
sudo apt install -y htop iotop nethogs

# 安裝系統資訊工具
sudo apt install -y neofetch sysstat

# 檢查系統資訊
neofetch
```

### 磁碟 I/O 監控
```bash
# 監控磁碟使用
df -h

# 監控 I/O 狀態
iotop

# 檢查磁碟效能
iostat -x 1
```

### 網路監控
```bash
# 監控網路連線
netstat -tunlp

# 監控網路流量
nethogs

# 檢查網路效能
iftop
```

---

## ✅ 環境驗證清單

部署前請確認以下項目：

### 系統需求
- [ ] CPU 核心數 ≥ 2
- [ ] 記憶體 ≥ 4GB
- [ ] 硬碟空間 ≥ 20GB
- [ ] 網路連線正常

### 軟體需求
- [ ] Docker 版本 ≥ 20.10
- [ ] Docker Compose 版本 ≥ 2.0
- [ ] Git 已安裝
- [ ] 基本工具已安裝

### 網路設定
- [ ] 防火牆允許端口 80, 443
- [ ] DNS 解析正常
- [ ] 網路連線穩定

### 安全設定
- [ ] 系統已更新到最新版本
- [ ] SSH 安全設定已完成
- [ ] 檔案權限設定正確
- [ ] SELinux 設定適當

### 效能調校
- [ ] 系統參數已調校
- [ ] Docker 設定已優化
- [ ] 監控工具已安裝

---

## 🔧 故障排除

### 常見問題

#### Docker 權限問題
```bash
# 將使用者加入 docker 群組
sudo usermod -aG docker $USER

# 重新登入生效
newgrp docker

# 驗證權限
docker run hello-world
```

#### 端口佔用問題
```bash
# 檢查端口佔用
sudo netstat -tulpn | grep :80

# 停止佔用端口的服務
sudo systemctl stop apache2  # Apache
sudo systemctl stop nginx    # Nginx

# 或強制終止程序
sudo fuser -k 80/tcp
```

#### 記憶體不足問題
```bash
# 檢查記憶體使用
free -h

# 檢查最大記憶體使用程序
ps aux --sort=-%mem | head

# 清理系統快取
sudo sync && sudo sysctl vm.drop_caches=3
```

#### 磁碟空間不足
```bash
# 檢查大型檔案
sudo find / -type f -size +100M 2>/dev/null | head -10

# 清理 Docker 暫存
docker system prune -f

# 清理系統日誌
sudo journalctl --vacuum-time=7d
```

---

## 📞 支援資源

### 官方文件
- [Docker 安裝指南](https://docs.docker.com/engine/install/)
- [Docker Compose 文件](https://docs.docker.com/compose/)
- [Ubuntu 伺服器指南](https://ubuntu.com/server/docs)

### 社群支援
- [Docker 社群論壇](https://forums.docker.com/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/docker)

### 相關文件
- [管理員快速入門](ADMIN_QUICK_START.md)
- [完整部署指南](DEPLOYMENT.md)
- [管理員操作手冊](ADMIN_MANUAL.md)

---

**📋 請在開始部署前仔細檢查所有系統需求，確保環境準備充分。**

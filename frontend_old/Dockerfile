# 多階段建構 Dockerfile for AlleyNote Frontend

# Stage 1: Build
FROM node:18-alpine AS builder

# 設定工作目錄
WORKDIR /app

# 複製 package.json 和 package-lock.json
COPY package*.json ./

# 安裝依賴
RUN npm ci --only=production

# 複製原始碼
COPY . .

# 建構應用程式
RUN npm run build

# Stage 2: Production
FROM nginx:alpine

# 複製自訂 nginx 配置
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 複製建構產物
COPY --from=builder /app/dist /usr/share/nginx/html

# 健康檢查
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD wget --no-verbose --tries=1 --spider http://localhost/ || exit 1

# 暴露端口
EXPOSE 80

# 啟動 nginx
CMD ["nginx", "-g", "daemon off;"]

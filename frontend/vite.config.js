import { defineConfig } from 'vite'

export default defineConfig({
  // 根目錄設定
  root: './public',
  
  // 建構配置
  build: {
    outDir: '../dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: './public/index.html'
      }
    }
  },
  
  // 開發伺服器配置
  server: {
    port: 3000,
    host: true,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false
      }
    }
  },
  
  // 預覽伺服器配置
  preview: {
    port: 3000,
    host: true
  },
  
  // 解析配置
  resolve: {
    alias: {
      '@': '/src'
    }
  },
  
  // CSS 配置
  css: {
    devSourcemap: true
  },
  
  // 環境變數前綴
  envPrefix: 'ALLEYNOTE_'
})

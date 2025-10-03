import { defineConfig } from "vite";
import { resolve } from "path";

export default defineConfig({
    // 建構配置
    build: {
        outDir: "./dist",
        emptyOutDir: true,
        
        // Code Splitting 優化
        rollupOptions: {
            input: "./public/index.html",
            output: {
                entryFileNames: "assets/[name]-[hash].js",
                chunkFileNames: "assets/[name]-[hash].js",
                assetFileNames: "assets/[name]-[hash].[ext]",
                
                // 手動分割代碼
                manualChunks: {
                    // 第三方套件單獨打包
                    'vendor-chart': ['chart.js'],
                    'vendor-ckeditor': ['@ckeditor/ckeditor5-build-classic'],
                    'vendor-core': ['axios', 'navigo', 'dompurify'],
                },
            },
        },
        
        // 啟用壓縮
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // 生產環境移除 console
                drop_debugger: true,
            },
        },
        
        // 資源內聯限制（小於 4KB 的資源會被內聯為 base64）
        assetsInlineLimit: 4096,
        
        // 啟用 CSS 代碼分割
        cssCodeSplit: true,
        
        // 產生 source map（生產環境建議關閉或使用 hidden）
        sourcemap: false,
        
        // 設定警告大小限制
        chunkSizeWarningLimit: 1000,
    },

    // 開發伺服器配置
    server: {
        port: 3000,
        host: true,
        proxy: {
            "/api": {
                target: "http://localhost:8080",
                changeOrigin: true,
                secure: false,
            },
        },
    },

    // 預覽伺服器配置
    preview: {
        port: 3000,
        host: true,
    },

    // 解析配置
    resolve: {
        alias: {
            "@": resolve(__dirname, "./src"),
        },
    },

    // CSS 配置
    css: {
        devSourcemap: true,
    },

    // 環境變數前綴
    envPrefix: "ALLEYNOTE_",
    
    // 優化配置
    optimizeDeps: {
        include: [
            'axios',
            'navigo',
            'dompurify',
            'validator',
            'chart.js',
        ],
    },
});

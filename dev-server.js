/**
 * 輕量開發伺服器：靜態檔案服務 + API 代理
 * 支持 SPA 路由（所有非檔案請求導向 index.html）
 *
 * 使用方法：
 *   node dev-server.js <static-dir> --port <port> --proxy <path>:<target>
 */

const http = require("http");
const https = require("https");
const fs = require("fs");
const path = require("path");

const MIME_TYPES = {
  ".html": "text/html",
  ".js": "text/javascript",
  ".css": "text/css",
  ".json": "application/json",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".gif": "image/gif",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
};

function parseArgs() {
  const args = {
    staticDir: process.argv[2] || ".",
    port: 3000,
    proxyPath: null,
    proxyTarget: null,
  };

  for (let i = 3; i < process.argv.length; i++) {
    if (process.argv[i] === "--port") {
      args.port = parseInt(process.argv[++i], 10);
    } else if (process.argv[i] === "--proxy") {
      const proxyArg = process.argv[++i];
      const colonIndex = proxyArg.indexOf(":");
      if (colonIndex !== -1) {
        args.proxyPath = proxyArg.substring(0, colonIndex);
        const t = proxyArg.substring(colonIndex + 1);
        args.proxyTarget = t.startsWith("http") ? t : `http://${t}`;
      }
    }
  }
  return args;
}

function serveStaticFile(res, filePath) {
  if (!fs.existsSync(filePath)) {
    console.error(`[static-error] File not found: ${filePath}`);
    res.writeHead(404);
    res.end(`Not Found: ${filePath}`);
    return;
  }

  try {
    const data = fs.readFileSync(filePath);
    const ext = path.extname(filePath).toLowerCase();
    res.writeHead(200, {
      "Content-Type": MIME_TYPES[ext] || "application/octet-stream",
      "Cache-Control": "no-cache",
    });
    res.end(data);
  } catch (err) {
    console.error(`[static-error] Read error ${filePath}: ${err.message}`);
    res.writeHead(500);
    res.end("Internal Server Error");
  }
}

function proxyRequest(req, res, targetUrl) {
  console.log(`[proxy] ${req.method} ${req.url} -> ${targetUrl}`);
  const url = new URL(targetUrl);
  const clientModule = url.protocol === "https:" ? https : http;
  const proxyReq = clientModule.request(
    {
      hostname: url.hostname,
      port: url.port || (url.protocol === "https:" ? 443 : 80),
      path: url.pathname + url.search,
      method: req.method,
      headers: { ...req.headers, host: url.host },
      timeout: 30000, // 30s timeout
    },
    (proxyRes) => {
      console.log(`[proxy-res] ${req.url} -> ${proxyRes.statusCode}`);
      res.writeHead(proxyRes.statusCode);
      Object.keys(proxyRes.headers).forEach((key) => {
        const value = proxyRes.headers[key];
        res.setHeader(key, value);
      });
      proxyRes.pipe(res);
    },
  );

  proxyReq.on("error", (err) => {
    console.error(`[proxy-error] ${req.url} -> ${err.message}`);
    if (!res.headersSent) {
      res.writeHead(502, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ error: "Proxy error", details: err.message }));
    }
  });

  req.pipe(proxyReq);
}

function main() {
  const args = parseArgs();
  console.log(`[dev-server] Starting with CWD: ${process.cwd()}`);
  console.log(`[dev-server] __dirname: ${__dirname}`);
  
  const staticRootDir = path.isAbsolute(args.staticDir) 
    ? args.staticDir 
    : path.resolve(process.cwd(), args.staticDir);

  const server = http.createServer((req, res) => {
    const parsedUrl = new URL(req.url, `http://localhost:${args.port}`);
    const urlPath = parsedUrl.pathname;
    const queryString = parsedUrl.search;

    console.log(`[request] ${req.method} ${req.url}`);

    // 1. API 代理
    if (args.proxyPath && (urlPath === args.proxyPath || urlPath.startsWith(args.proxyPath + "/"))) {
      const targetUrl = new URL(args.proxyTarget);
      const proxyUrl = targetUrl.origin + urlPath + queryString;
      proxyRequest(req, res, proxyUrl);
      return;
    }

    // 2. 靜態檔案
    const decodedPath = decodeURIComponent(urlPath);
    let filePath = path.join(staticRootDir, decodedPath === "/" ? "index.html" : decodedPath);

    // 檢查檔案是否存在
    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      console.log(`[static] Serving file: ${filePath}`);
      serveStaticFile(res, filePath);
    } else {
      // SPA 模式：如果不是檔案，回傳 index.html
      const indexPath = path.join(staticRootDir, "index.html");
      console.log(`[static-spa] Routing to index.html for: ${urlPath}`);
      serveStaticFile(res, indexPath);
    }
  });

  server.listen(args.port, "0.0.0.0", () => {
    console.log(`[dev-server] Listening on http://0.0.0.0:${args.port}`);
    console.log(`[dev-server] Static root: ${staticRootDir}`);
    if (args.proxyPath) {
      console.log(`[dev-server] Proxy: ${args.proxyPath} -> ${args.proxyTarget}`);
    }
  });

  process.on("SIGTERM", () => server.close(() => process.exit(0)));
  process.on("SIGINT", () => server.close(() => process.exit(0)));
}

main();

/**
 * 輕量開發伺服器：靜態檔案服務 + API 代理
 * 取代 live-server 以避免供應鏈漏洞（chokidar -> braces/micromatch/readdirp）
 * 使用 Node.js 原生 http.request 代理，不依賴第三方套件
 *
 * 使用方法：
 *   node dev-server.js <static-dir> --port <port> --proxy <path>:<target>
 *
 * 範例：
 *   node dev-server.js ./frontend --port 3000 --proxy /api:http://127.0.0.1:8081/api
 */

const http = require("http");
const fs = require("fs");
const path = require("path");
const { URL } = require("url");

function parseArgs(argv) {
  const args = {
    staticDir: ".",
    port: 3000,
    proxyPath: null,
    proxyTarget: null,
  };
  let i = 2;
  if (i < argv.length && !argv[i].startsWith("--")) {
    args.staticDir = argv[i++];
  }
  while (i < argv.length) {
    if (argv[i] === "--port" && i + 1 < argv.length) {
      args.port = parseInt(argv[++i], 10);
    } else if (argv[i] === "--proxy" && i + 1 < argv.length) {
      const proxyArg = argv[++i];
      const colonIndex = proxyArg.indexOf(":");
      args.proxyPath = proxyArg.substring(0, colonIndex);
      args.proxyTarget = proxyArg.substring(colonIndex + 1);
    }
    i++;
  }
  return args;
}

const MIME_TYPES = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".gif": "image/gif",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".woff": "font/woff",
  ".woff2": "font/woff2",
  ".ttf": "font/ttf",
  ".eot": "application/vnd.ms-fontobject",
};

function serveStaticFile(res, filePath) {
  fs.readFile(filePath, (err, data) => {
    if (err) {
      res.writeHead(404);
      res.end("Not Found");
      return;
    }
    const ext = path.extname(filePath).toLowerCase();
    res.writeHead(200, {
      "Content-Type": MIME_TYPES[ext] || "application/octet-stream",
    });
    res.end(data);
  });
}

function proxyRequest(req, res, targetUrl) {
  const url = new URL(targetUrl);
  const proxyReq = http.request(
    {
      hostname: url.hostname,
      port: url.port || (url.protocol === "https:" ? 443 : 80),
      path: url.pathname + url.search,
      method: req.method,
      headers: { ...req.headers, host: url.host },
    },
    (proxyRes) => {
      res.writeHead(proxyRes.statusCode, proxyRes.headers);
      proxyRes.pipe(res);
    },
  );

  proxyReq.on("error", (err) => {
    console.error("[proxy error]", err.message);
    if (!res.headersSent) {
      res.writeHead(502, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ error: "Proxy error", message: err.message }));
    }
  });

  req.pipe(proxyReq);
}

function main() {
  const args = parseArgs(process.argv);

  const server = http.createServer((req, res) => {
    // 解析 URL 以取得乾淨的路徑（不含查詢字串）
    const parsedUrl = new URL(req.url, `http://localhost:${args.port}`);
    const urlPath = parsedUrl.pathname;
    const queryString = parsedUrl.search;

    // 代理請求：精確匹配路徑前綴（避免 /api.evil.com 被誤判）
    if (
      args.proxyPath &&
      (urlPath === args.proxyPath || urlPath.startsWith(args.proxyPath + "/"))
    ) {
      const targetPath = urlPath.substring(args.proxyPath.length);
      const targetUrl =
        args.proxyTarget.replace(/\/$/, "") + args.proxyPath + targetPath;
      proxyRequest(req, res, targetUrl + queryString);
      return;
    }

    // 靜態檔案：先解碼 URL 再解析路徑，防止 %2e%2e 繞過
    let decodedPath;
    try {
      decodedPath = decodeURIComponent(urlPath);
    } catch {
      res.writeHead(400);
      res.end("Bad Request");
      return;
    }

    let filePath = path.join(
      args.staticDir,
      decodedPath === "/" ? "index.html" : decodedPath,
    );
    const resolved = path.resolve(filePath);
    const staticResolved = path.resolve(args.staticDir);

    // 防止路徑穿越：確保解析後的路徑在靜態目錄內
    // 使用 staticResolved + path.sep 避免同前綴路徑繞過
    // 例如：/repo/frontend-secrets 不會被 /repo/frontend 匹配
    if (
      resolved !== staticResolved &&
      !resolved.startsWith(staticResolved + path.sep)
    ) {
      res.writeHead(403);
      res.end("Forbidden");
      return;
    }

    if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
      serveStaticFile(res, filePath);
      return;
    }

    const indexPath = path.join(args.staticDir, "index.html");
    if (fs.existsSync(indexPath)) {
      serveStaticFile(res, indexPath);
      return;
    }

    res.writeHead(404);
    res.end("Not Found");
  });

  server.listen(args.port, "0.0.0.0", () => {
    console.log(`[dev-server] serving ${args.staticDir} on :${args.port}`);
    if (args.proxyPath) {
      console.log(
        `[dev-server] proxy ${args.proxyPath} -> ${args.proxyTarget}`,
      );
    }
  });

  process.on("SIGTERM", () => {
    console.log("[dev-server] SIGTERM received, shutting down gracefully");
    server.close(() => process.exit(0));
  });

  process.on("SIGINT", () => {
    console.log("[dev-server] SIGINT received, shutting down gracefully");
    server.close(() => process.exit(0));
  });
}

main();

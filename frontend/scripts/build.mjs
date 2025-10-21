import { mkdir, cp, rm, readFile, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const frontendDir = path.resolve(__dirname, "..");
const distDir = path.join(frontendDir, "dist");
const assetsDir = path.join(distDir, "assets");

async function prepareDist() {
    await rm(distDir, { recursive: true, force: true });
    await mkdir(distDir, { recursive: true });
    await mkdir(assetsDir, { recursive: true });
}

async function copyStaticAssets() {
    const targets = ["index.html", "favicon.ico"];

    for (const item of targets) {
        const source = path.join(frontendDir, item);
        const destination = path.join(distDir, item);
        await cp(source, destination);
    }

    const directories = ["css", "js", "assets"];
    for (const dir of directories) {
        const source = path.join(frontendDir, dir);
        const destination = path.join(distDir, dir);
        await cp(source, destination, { recursive: true });
    }
}

async function createMainBundle() {
    const mainSource = path.join(frontendDir, "js", "main.js");
    const bundleTarget = path.join(assetsDir, "main-static.js");
    const content = await readFile(mainSource, "utf-8");
    await writeFile(bundleTarget, content, "utf-8");
}

async function run() {
    await prepareDist();
    await copyStaticAssets();
    await createMainBundle();
    console.log("✅ Static frontend assets prepared in dist/.");
}

run().catch((error) => {
    console.error("❌ Failed to build static assets:", error);
    process.exitCode = 1;
});

<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/**
 * 掃描專案架構並產生報告
 *
 * @author CookeyHolder
 */
class ProjectScanner
{
    private string $rootPath;
    private array $report = [];
    private const DDD_LAYERS = [
        'Domain' => 'backend/app/Domains',
        'Application' => 'backend/app/Application',
        'Infrastructure' => 'backend/app/Infrastructure',
        'Interface' => 'backend/app/Http', // Controllers, Middleware, etc.
    ];

    public function __construct(string $rootPath)
    {
        $this->rootPath = realpath($rootPath);
        if ($this->rootPath === false) {
            throw new InvalidArgumentException("無效的根路徑: {$rootPath}");
        }
    }

    public function scan(): self
    {
        $this->report['project_name'] = basename($this->rootPath);
        $this->report['scan_time'] = date('Y-m-d H:i:s');
        $this->report['analysis'] = $this->analyze();

        return $this;
    }

    public function generateReport(string $format = 'json'): string
    {
        switch ($format) {
            case 'json':
                return json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            case 'markdown':
                return $this->generateMarkdownReport();
            default:
                throw new InvalidArgumentException("不支援的報告格式: {$format}");
        }
    }

    private function analyze(): array
    {
        $analysis = [];
        $analysis['composer'] = $this->analyzeComposer();
        $analysis['routes'] = $this->analyzeRoutes();
        $analysis['migrations'] = $this->analyzeMigrations();
        $analysis['layer_analysis'] = $this->analyzeLayers();
        $analysis['total_files'] = $this->countTotalFiles($analysis['layer_analysis']);

        return $analysis;
    }

    private function analyzeComposer(): array
    {
        $composerPath = $this->rootPath . '/backend/composer.json';
        if (!file_exists($composerPath)) {
            return ['error' => 'composer.json not found'];
        }
        $composerData = json_decode(file_get_contents($composerPath), true);
        return [
            'require' => array_keys($composerData['require'] ?? []),
            'require-dev' => array_keys($composerData['require-dev'] ?? []),
        ];
    }

    private function analyzeRoutes(): array
    {
        $routesPath = $this->rootPath . '/backend/config/routes.php';
        if (!file_exists($routesPath)) {
            return ['error' => 'routes.php not found'];
        }

        // This is a simplified parser. It might not catch all edge cases.
        $content = file_get_contents($routesPath);
        $pattern = '/\$app->(get|post|put|patch|delete|options)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^\)]+)\)/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $routes = [];
        foreach ($matches as $match) {
            $routes[] = [
                'method' => strtoupper($match[1]),
                'path' => $match[2],
                'handler' => trim($match[3]),
            ];
        }
        return $routes;
    }

    private function analyzeMigrations(): array
    {
        $migrationsPath = $this->rootPath . '/backend/database/migrations';
        if (!is_dir($migrationsPath)) {
            return ['error' => 'Migrations directory not found'];
        }
        $finder = new Finder();
        $finder->in($migrationsPath)->files()->name('*.php')->sortByName();
        $migrations = [];
        foreach ($finder as $file) {
            $migrations[] = $file->getFilename();
        }
        return $migrations;
    }

    private function countTotalFiles(array $layerAnalysis): int
    {
        return array_reduce($layerAnalysis, fn ($carry, $item) => $carry + ($item['file_count'] ?? 0), 0);
    }

    private function analyzeLayers(): array
    {
        $analysis = [];
        foreach (self::DDD_LAYERS as $name => $path) {
            $fullPath = $this->rootPath . '/' . $path;
            if (is_dir($fullPath)) {
                $finder = new Finder();
                $finder->in($fullPath)->files()->name('*.php');
                $analysis[$name] = [
                    'path' => $path,
                    'file_count' => $finder->count(),
                    'dependencies' => $this->analyzeDependencies($finder, $name),
                ];
            }
        }
        return $analysis;
    }

    private function analyzeDependencies(Finder $finder, string $currentLayer): array
    {
        $dependencies = [];
        foreach (self::DDD_LAYERS as $name => $path) {
            if ($name !== $currentLayer) {
                $dependencies[$name] = 0;
            }
        }

        $layerNamespaces = [];
        foreach (self::DDD_LAYERS as $name => $path) {
            // Assuming App\ is the root namespace for these layers
            $layerNamespaces[$name] = 'App\\' . str_replace('/', '\\', dirname($path));
        }

        foreach ($finder as $file) {
            $content = $file->getContents();
            foreach ($layerNamespaces as $name => $namespace) {
                if ($name !== $currentLayer) {
                    // A simple regex to find `use` statements for a given namespace
                    if (preg_match_all("/use\s+{$namespace}[^;]*/", $content, $matches)) {
                        $dependencies[$name] += count($matches[0]);
                    }
                }
            }
        }

        return $dependencies;
    }

    private function generateMarkdownReport(): string
    {
        $md = "# 專案架構報告: {$this->report['project_name']}\n\n";
        $md .= "**掃描時間:** {$this->report['scan_time']}\n\n";

        $md .= "## 總體分析\n\n";
        $md .= "- **PHP 總檔案數 (DDD 層級):** {$this->report['analysis']['total_files']}\n";
        $md .= "- **API 路由數量:** " . count($this->report['analysis']['routes']) . "\n";
        $md .= "- **資料庫遷移檔案數:** " . count($this->report['analysis']['migrations']) . "\n\n";

        $md .= "## 分層架構分析 (DDD)\n\n";
        $md .= "| 層級 (Layer) | 路徑 | 檔案數 | 依賴分析 (違規以星號*標示) |\n";
        $md .= "|---|---|---|---|\n";

        $validDependencies = [
            'Interface' => ['Application', 'Domain'],
            'Application' => ['Domain'],
            'Infrastructure' => ['Application', 'Domain'],
            'Domain' => [],
        ];

        foreach ($this->report['analysis']['layer_analysis'] as $name => $data) {
            $depStrings = [];
            foreach ($data['dependencies'] as $depName => $count) {
                if ($count > 0) {
                    $isInvalid = !in_array($depName, $validDependencies[$name] ?? []);
                    $marker = $isInvalid ? ' (*)' : '';
                    $depStrings[] = "{$depName} ({$count}){$marker}";
                }
            }
            $dependencies = empty($depStrings) ? '無' : implode(', ', $depStrings);
            $md .= "| **{$name}** | `{$data['path']}` | {$data['file_count']} | {$dependencies} |\n";
        }
        $md .= "\n> `(*)` 星號表示可能違反 DDD 依賴原則的參考。\n\n";

        $md .= "## Composer 相依套件\n\n";
        $md .= "### 正式相依 (require)\n\n";
        $md .= "```\n" . implode("\n", $this->report['analysis']['composer']['require'] ?? ['無']) . "\n```\n\n";
        $md .= "### 開發相依 (require-dev)\n\n";
        $md .= "```\n" . implode("\n", $this->report['analysis']['composer']['require-dev'] ?? ['無']) . "\n```\n\n";

        $md .= "## API 路由列表\n\n";
        $md .= "| 方法 | 路徑 | 處理器 |\n";
        $md .= "|---|---|---|\n";
        foreach ($this->report['analysis']['routes'] as $route) {
            $handler = str_replace("\n", " ", $route['handler']);
            $md .= "| `{$route['method']}` | `{$route['path']}` | `{$handler}` |\n";
        }
        $md .= "\n";

        $md .= "## 資料庫遷移檔案\n\n";
        $md .= "```\n" . implode("\n", $this->report['analysis']['migrations'] ?? ['無']) . "\n```\n\n";

        return $md;
    }
}

// --- 執行掃描 ---
try {
    $scanner = new ProjectScanner(__DIR__ . '/../');
    $scanner->scan();
    echo $scanner->generateReport('markdown');
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}

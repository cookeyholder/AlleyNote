<?php
/**
 * 修復控制器中的孤立 catch 區塊
 */

$filesToFix = [
    '/var/www/html/app/Application/Controllers/Api/V1/AuthController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/IpController.php',
    '/var/www/html/app/Application/Controllers/Api/V1/PostController.php',
];

$issuesFixed = 0;

foreach ($filesToFix as $filePath) {
    echo "修復檔案: " . basename($filePath) . "\n";

    $content = file_get_contents($filePath);
    $originalContent = $content;

    // 修復模式1: } catch (\Exception $e) { throw $e; } 這種無用的 catch
    $content = preg_replace('/\s*catch\s*\(\s*\\\\?Exception\s+\$e\s*\)\s*\{\s*\/\/[^\}]*\s*throw\s+\$e;\s*\}/', '', $content);

    // 修復模式2: 孤立的 catch (\Exception $e) { ... }
    $lines = explode("\n", $content);
    $newLines = [];
    $skipLines = [];

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        // 檢查是否為孤立的 catch
        if (preg_match('/^catch\s*\(/', $line) && $i > 0) {
            $prevLine = trim($lines[$i - 1]);

            // 如果前一行不是 } 或者沒有對應的 try，這是孤立的 catch
            if (!preg_match('/^\}$/', $prevLine) || !$this->hasPreviousTry($lines, $i)) {
                // 找到 catch 區塊的結束
                $braceCount = substr_count($lines[$i], '{') - substr_count($lines[$i], '}');
                $j = $i + 1;

                while ($j < count($lines) && $braceCount > 0) {
                    $braceCount += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
                    $j++;
                }

                // 標記跳過這些行
                for ($k = $i; $k < $j; $k++) {
                    $skipLines[] = $k;
                }

                echo "  移除孤立的 catch 區塊 (行 " . ($i + 1) . " 到 " . $j . ")\n";
                $issuesFixed++;
            }
        }
    }

    // 重建檔案內容
    $finalLines = [];
    for ($i = 0; $i < count($lines); $i++) {
        if (!in_array($i, $skipLines)) {
            $finalLines[] = $lines[$i];
        }
    }

    $content = implode("\n", $finalLines);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "  已修復並儲存\n";
    } else {
        echo "  無需修復\n";
    }
}

function hasPreviousTry($lines, $catchIndex) {
    $braceCount = 0;

    for ($i = $catchIndex - 1; $i >= 0; $i--) {
        $line = trim($lines[$i]);

        $braceCount += substr_count($lines[$i], '}') - substr_count($lines[$i], '{');

        if (preg_match('/try\s*\{/', $line) && $braceCount >= 0) {
            return true;
        }

        if (preg_match('/^\s*(public|private|protected)?\s*function\s+/', $line)) {
            break;
        }
    }

    return false;
}

echo "\n修復完成！總計修復 $issuesFixed 個問題\n";

<?php

declare(strict_types=1);

/**
 * 手動重設管理員密碼腳本
 */

try {
    $dbPath = dirname(__DIR__) . '/backend/database/alleynote.sqlite3';

    if (!file_exists($dbPath)) {
        echo "❌ 找不到資料庫檔案: {$dbPath}\n";
        echo "請先完成資料庫初始化（例如執行 migrate）。\n";
        exit(1);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $email = 'admin@example.com';
    $newPassword = 'Admin@123456';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // 取得 users 欄位資訊，支援不同版本 schema
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columns)) {
        echo "❌ users 資料表不存在，請先執行資料庫 migration。\n";
        exit(1);
    }

    $columnNames = array_map(static fn(array $column): string => (string) $column['name'], $columns);
    $passwordColumn = in_array('password_hash', $columnNames, true) ? 'password_hash' : 'password';
    if (!in_array($passwordColumn, $columnNames, true)) {
        echo "❌ 找不到可用的密碼欄位（password_hash / password）。\n";
        exit(1);
    }

    // 先確保使用者存在，不存在就建立
    $findUserStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $findUserStmt->execute(['email' => $email]);
    $userId = $findUserStmt->fetchColumn();

    if (!$userId) {
        $insertColumns = ['username', 'email', $passwordColumn];
        $insertParams = [
            ':username' => 'admin',
            ':email' => $email,
            ':password' => $hashedPassword,
        ];

        if (in_array('uuid', $columnNames, true)) {
            $insertColumns[] = 'uuid';
            $insertParams[':uuid'] = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        }

        if (in_array('is_active', $columnNames, true)) {
            $insertColumns[] = 'is_active';
            $insertParams[':is_active'] = 1;
        }

        $insertPlaceholders = array_map(
            static fn(string $column): string => match ($column) {
                'username' => ':username',
                'email' => ':email',
                'uuid' => ':uuid',
                'is_active' => ':is_active',
                default => ':password',
            },
            $insertColumns
        );

        $insertSql = sprintf(
            'INSERT INTO users (%s) VALUES (%s)',
            implode(', ', $insertColumns),
            implode(', ', $insertPlaceholders)
        );

        $pdo->prepare($insertSql)->execute($insertParams);
        $userId = (int) $pdo->lastInsertId();
        echo "✓ 已建立管理員帳號 {$email}\n";
    }

    // 更新密碼與啟用狀態（若欄位存在）
    $updates = ["{$passwordColumn} = :password"];
    $params = [':password' => $hashedPassword, ':email' => $email];
    if (in_array('is_active', $columnNames, true)) {
        $updates[] = 'is_active = :is_active';
        $params[':is_active'] = 1;
    }

    $updateSql = sprintf('UPDATE users SET %s WHERE email = :email', implode(', ', $updates));
    $pdo->prepare($updateSql)->execute($params);

    echo "✓ 已成功將 {$email} 的密碼重設為: {$newPassword}\n";
    if (in_array('is_active', $columnNames, true)) {
        echo "✓ 帳號已確保為啟用狀態 (is_active = 1)\n";
    }

    // 確保使用者具備管理角色
    // 檢查 user_roles 表是否存在
    $hasUserRolesTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_roles'")->fetch();

    if ($hasUserRolesTable) {
        $roleIdStmt = $pdo->prepare("SELECT id FROM roles WHERE name IN ('super_admin', 'admin') ORDER BY CASE name WHEN 'super_admin' THEN 0 ELSE 1 END LIMIT 1");
        $roleIdStmt->execute();
        $roleId = $roleIdStmt->fetchColumn();

        if ($roleId) {
            $userRoleColumns = $pdo->query("PRAGMA table_info(user_roles)")->fetchAll(PDO::FETCH_ASSOC);
            $userRoleColumnNames = array_map(static fn(array $column): string => (string) $column['name'], $userRoleColumns);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = :user_id AND role_id = :role_id");
            $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);
            $exists = (int) $stmt->fetchColumn() > 0;

            if (!$exists) {
                if (in_array('assigned_at', $userRoleColumnNames, true)) {
                    $pdo->prepare("INSERT INTO user_roles (user_id, role_id, assigned_at) VALUES (:user_id, :role_id, :assigned_at)")
                        ->execute([
                            'user_id' => $userId,
                            'role_id' => $roleId,
                            'assigned_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)")
                        ->execute(['user_id' => $userId, 'role_id' => $roleId]);
                }
                echo "✓ 已將使用者關聯至管理角色\n";
            } else {
                echo "✓ 使用者已具備管理角色\n";
            }
        }
    }

    echo "\n✅ 重設完成！請嘗試使用以下憑證登入：\n";
    echo "帳號: {$email}\n";
    echo "密碼: {$newPassword}\n";

} catch (Exception $e) {
    echo "❌ 錯誤: " . $e->getMessage() . "\n";
    exit(1);
}

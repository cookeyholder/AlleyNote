<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use DateTime;
use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 文章管理控制器.
 *
 * 注意：此控制器專為管理員後台設計，所有操作都直接與資料庫互動，
 * 不使用快取機制，以確保管理員看到的資料始終是最新的。
 *
 * 快取策略：
 * - 讀取操作：直接從資料庫查詢（管理員需要即時資料）
 * - 寫入操作：直接寫入資料庫（新增、修改、刪除）
 *
 * 前台使用者查看文章時，應使用 ApiPostController，其中實作了適當的快取策略。
 */
class PostController extends BaseController
{
    /**
     * 取得所有貼文（管理員後台，不使用快取）.
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // 獲取查詢參數
            $queryParams = $request->getQueryParams();
            $page = max(1, (int) ($queryParams['page'] ?? 1));
            $perPage = min(100, max(1, (int) ($queryParams['per_page'] ?? 10)));
            $search = $queryParams['search'] ?? '';
            $status = $queryParams['status'] ?? '';
            $includeFuture = filter_var($queryParams['include_future'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // 建立資料庫連接
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 建立查詢
            $where = ['deleted_at IS NULL'];
            $params = [];

            if (!empty($search)) {
                $where[] = '(title LIKE :search OR content LIKE :search)';
                $params[':search'] = "%{$search}%";
            }

            if (!empty($status)) {
                $where[] = 'status = :status';
                $params[':status'] = $status;
            }

            // 根據 include_future 參數決定是否過濾未來文章
            // 預設為 false（過濾未來文章，用於首頁等公開頁面）
            // 設為 true 時顯示所有文章（用於文章管理頁面）
            if (!$includeFuture) {
                $where[] = "(publish_date IS NULL OR publish_date <= datetime('now'))";
            }

            $whereClause = implode(' AND ', $where);

            // 計算總數
            $countSql = "SELECT COUNT(*) as total FROM posts WHERE {$whereClause}";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();

            // 獲取資料
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT p.id, p.title, p.content, p.status, p.user_id, p.created_at, p.updated_at, p.publish_date,
                           u.username as author
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE {$whereClause} 
                    ORDER BY COALESCE(p.publish_date, p.created_at) DESC 
                    LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 確保每個 post 都有 author 欄位
            $posts = array_map(function ($post) {
                $post['author'] ??= 'Unknown';

                return $post;
            }, $posts);

            // 格式化回應
            $responseData = $this->paginatedResponse($posts, $total, $page, $perPage);
            $response->getBody()->write($responseData);

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->errorResponse('Failed to fetch posts: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得單一貼文（管理員後台，不使用快取）.
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        try {
            // 獲取查詢參數
            $queryParams = $request->getQueryParams();
            $includeFuture = filter_var($queryParams['include_future'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // 建立資料庫連接
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 建立查詢條件
            $conditions = ['p.id = :id', 'p.deleted_at IS NULL'];

            // 根據 include_future 參數決定是否過濾
            // 當 include_future=false（公開訪問）時：
            // - 只顯示已發布的文章
            // - 過濾未來的文章
            if (!$includeFuture) {
                $conditions[] = "p.status = 'published'";
                $conditions[] = "(p.publish_date IS NULL OR p.publish_date <= datetime('now'))";
            }

            $whereClause = implode(' AND ', $conditions);

            // 查詢文章
            $sql = "SELECT p.*, u.username as author
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE {$whereClause}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                $errorResponse = $this->errorResponse('找不到指定的文章', 404);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 確保 author 欄位存在
            $post['author'] ??= 'Unknown';

            // 查詢文章的標籤
            $tagsSql = 'SELECT t.id, t.name 
                       FROM tags t
                       INNER JOIN post_tags pt ON t.id = pt.tag_id
                       WHERE pt.post_id = :post_id
                       ORDER BY t.name';
            $tagsStmt = $pdo->prepare($tagsSql);
            $tagsStmt->execute([':post_id' => $id]);
            $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($post)) {
                $post['tags'] = $tags;
            }

            $response->getBody()->write($this->successResponse($post));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->errorResponse('取得文章失敗: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 建立新貼文.
     */
    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();

            // 驗證必要欄位
            $title = $body['title'] ?? null;
            $content = $body['content'] ?? null;

            if (empty($title) || empty($content)) {
                $errorResponse = $this->errorResponse('標題和內容為必填欄位', 422);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
            }

            // 獲取使用者 ID（從 JWT token）
            $userId = $request->getAttribute('user_id') ?? 1;
            $status = $body['status'] ?? 'draft';

            // 處理發布日期
            $publishDate = null;
            if (!empty($body['publish_date'])) {
                try {
                    $date = new DateTime($body['publish_date']);
                    $publishDate = $date->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                    // 如果日期格式錯誤，使用當前時間
                    $publishDate = date('Y-m-d H:i:s');
                }
            } elseif ($status === 'published') {
                // 如果狀態是已發布但沒有指定日期，使用當前時間
                $publishDate = date('Y-m-d H:i:s');
            }

            // 建立資料庫連接
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 生成 UUID
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
            );

            // 獲取最新的 seq_number
            $seqStmt = $pdo->query('SELECT MAX(seq_number) as max_seq FROM posts');
            $maxSeq = $seqStmt->fetchColumn();
            $seqNumber = ($maxSeq ?? 0) + 1;

            // 插入新文章
            $sql = "INSERT INTO posts (uuid, seq_number, title, content, user_id, status, views, is_pinned, publish_date, created_at) 
                    VALUES (:uuid, :seq_number, :title, :content, :user_id, :status, 0, 0, :publish_date, datetime('now'))";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uuid' => $uuid,
                ':seq_number' => $seqNumber,
                ':title' => $title,
                ':content' => $content,
                ':user_id' => $userId,
                ':status' => $status,
                ':publish_date' => $publishDate,
            ]);

            $postId = $pdo->lastInsertId();

            // 處理標籤關聯
            $bodyArray = is_array($body) ? $body : [];
            if (isset($bodyArray['tag_ids']) && is_array($bodyArray['tag_ids']) && !empty($bodyArray['tag_ids'])) {
                $tagInsertSql = "INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (:post_id, :tag_id, datetime('now'))";
                $tagStmt = $pdo->prepare($tagInsertSql);

                foreach ($bodyArray['tag_ids'] as $tagId) {
                    if (is_numeric($tagId)) {
                        $tagStmt->execute([
                            ':post_id' => $postId,
                            ':tag_id' => (int) $tagId,
                        ]);

                        // 更新標籤的使用次數
                        $this->updateTagUsageCount($pdo, (int) $tagId);
                    }
                }
            }

            // 回傳新建立的文章
            $post = [
                'id' => (int) $postId,
                'uuid' => $uuid,
                'seq_number' => $seqNumber,
                'title' => $title,
                'content' => $content,
                'user_id' => $userId,
                'status' => $status,
                'publish_date' => $publishDate,
                'created_at' => date('c'),
                'tags' => [],
            ];

            // 查詢並回傳標籤資訊
            if (isset($bodyArray['tag_ids']) && !empty($bodyArray['tag_ids'])) {
                $tagsSql = 'SELECT t.id, t.name 
                           FROM tags t
                           INNER JOIN post_tags pt ON t.id = pt.tag_id
                           WHERE pt.post_id = :post_id';
                $tagsStmt = $pdo->prepare($tagsSql);
                $tagsStmt->execute([':post_id' => $postId]);
                $post['tags'] = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $response->getBody()->write($this->successResponse($post, '貼文建立成功'));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->errorResponse('建立文章失敗: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 更新貼文（管理員後台，直接寫入資料庫）.
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        try {
            // 手動解析 PUT/PATCH 請求的 body（Slim 預設不解析）
            $body = $request->getParsedBody();
            if (empty($body)) {
                $rawBody = (string) $request->getBody();
                if (!empty($rawBody)) {
                    $body = json_decode($rawBody, true);
                }
            }

            if (empty($body)) {
                $errorResponse = $this->errorResponse('請求內容不能為空', 422);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
            }

            // 建立資料庫連接
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 檢查文章是否存在
            $checkSql = 'SELECT id FROM posts WHERE id = :id AND deleted_at IS NULL';
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':id' => $id]);

            if (!$checkStmt->fetch()) {
                $errorResponse = $this->errorResponse('找不到指定的文章', 404);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 準備更新的欄位
            $updateFields = [];
            $params = [':id' => $id];

            if (isset($body['title'])) {
                $updateFields[] = 'title = :title';
                $params[':title'] = $body['title'];
            }

            if (isset($body['content'])) {
                $updateFields[] = 'content = :content';
                $params[':content'] = $body['content'];
            }

            if (isset($body['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = $body['status'];
            }

            if (isset($body['excerpt'])) {
                $updateFields[] = 'excerpt = :excerpt';
                $params[':excerpt'] = $body['excerpt'];
            }

            // 處理發布日期
            if (isset($body['publish_date'])) {
                if (!empty($body['publish_date'])) {
                    try {
                        $date = new DateTime($body['publish_date']);
                        $updateFields[] = 'publish_date = :publish_date';
                        $params[':publish_date'] = $date->format('Y-m-d H:i:s');
                    } catch (Exception $e) {
                        // 日期格式錯誤，忽略
                    }
                } else {
                    // 空值表示清除發布日期
                    $updateFields[] = 'publish_date = NULL';
                }
            }

            if (empty($updateFields)) {
                $errorResponse = $this->errorResponse('沒有要更新的欄位', 422);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
            }

            // 添加更新時間
            $updateFields[] = 'updated_at = datetime(\'now\')';

            // 執行更新
            $sql = 'UPDATE posts SET ' . implode(', ', $updateFields) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // 處理標籤關聯更新
            $bodyArray = is_array($body) ? $body : [];
            if (isset($bodyArray['tag_ids']) && is_array($bodyArray['tag_ids'])) {
                // 取得舊的標籤關聯以更新計數
                $oldTagsSql = 'SELECT tag_id FROM post_tags WHERE post_id = :post_id';
                $oldTagsStmt = $pdo->prepare($oldTagsSql);
                $oldTagsStmt->execute([':post_id' => $id]);
                $oldTagRows = $oldTagsStmt->fetchAll(PDO::FETCH_ASSOC);
                /** @var list<int> $oldTagIds */
                $oldTagIds = array_map(
                    function ($row) {
                        if (is_array($row) && isset($row['tag_id']) && (is_int($row['tag_id']) || is_string($row['tag_id']))) {
                            return (int) $row['tag_id'];
                        }

                        return 0;
                    },
                    $oldTagRows,
                );

                // 先刪除舊的標籤關聯
                $deleteTagsSql = 'DELETE FROM post_tags WHERE post_id = :post_id';
                $deleteTagsStmt = $pdo->prepare($deleteTagsSql);
                $deleteTagsStmt->execute([':post_id' => $id]);

                // 更新被移除標籤的使用次數
                foreach ($oldTagIds as $oldTagId) {
                    $this->updateTagUsageCount($pdo, $oldTagId);
                }

                // 新增新的標籤關聯
                if (!empty($bodyArray['tag_ids'])) {
                    $tagInsertSql = "INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (:post_id, :tag_id, datetime('now'))";
                    $tagStmt = $pdo->prepare($tagInsertSql);

                    foreach ($bodyArray['tag_ids'] as $tagId) {
                        if (is_numeric($tagId)) {
                            $tagStmt->execute([
                                ':post_id' => $id,
                                ':tag_id' => (int) $tagId,
                            ]);

                            // 更新新增標籤的使用次數
                            $this->updateTagUsageCount($pdo, (int) $tagId);
                        }
                    }
                }
            }

            // 取得更新後的文章
            $getSql = 'SELECT p.*, u.username as author
                       FROM posts p
                       LEFT JOIN users u ON p.user_id = u.id
                       WHERE p.id = :id';
            $getStmt = $pdo->prepare($getSql);
            $getStmt->execute([':id' => $id]);
            $post = $getStmt->fetch(PDO::FETCH_ASSOC);

            // 查詢文章的標籤
            $tagsSql = 'SELECT t.id, t.name 
                       FROM tags t
                       INNER JOIN post_tags pt ON t.id = pt.tag_id
                       WHERE pt.post_id = :post_id
                       ORDER BY t.name';
            $tagsStmt = $pdo->prepare($tagsSql);
            $tagsStmt->execute([':post_id' => $id]);
            $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($post)) {
                $post['tags'] = $tags;
            }

            $response->getBody()->write($this->successResponse($post, '貼文更新成功'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->errorResponse('更新文章失敗: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 刪除貼文（管理員後台，軟刪除）.
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        try {
            // 建立資料庫連接
            $dbPath = $_ENV['DB_DATABASE'] ?? '/var/www/html/database/alleynote.sqlite3';
            $pdo = new PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 檢查文章是否存在
            $checkSql = 'SELECT id, title FROM posts WHERE id = :id AND deleted_at IS NULL';
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([':id' => $id]);
            $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                $errorResponse = $this->errorResponse('找不到指定的文章或文章已被刪除', 404);
                $response->getBody()->write($errorResponse);

                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // 取得文章的標籤以更新使用次數
            $tagsSql = 'SELECT tag_id FROM post_tags WHERE post_id = :post_id';
            $tagsStmt = $pdo->prepare($tagsSql);
            $tagsStmt->execute([':post_id' => $id]);
            $tagRows = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
            /** @var list<int> $tagIds */
            $tagIds = array_map(
                function ($row) {
                    if (is_array($row) && isset($row['tag_id']) && (is_int($row['tag_id']) || is_string($row['tag_id']))) {
                        return (int) $row['tag_id'];
                    }

                    return 0;
                },
                $tagRows,
            );

            // 執行軟刪除（設定 deleted_at）
            $sql = "UPDATE posts SET deleted_at = datetime('now') WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            // 更新受影響標籤的使用次數
            foreach ($tagIds as $tagId) {
                $this->updateTagUsageCount($pdo, $tagId);
            }

            $result = [
                'id' => $id,
                'title' => $post['title'],
                'deleted_at' => date('c'),
            ];

            $response->getBody()->write($this->successResponse($result, '貼文刪除成功'));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $errorResponse = $this->errorResponse('刪除文章失敗: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 更新標籤的使用次數.
     */
    private function updateTagUsageCount(PDO $pdo, int $tagId): void
    {
        $sql = 'UPDATE tags SET usage_count = (
                    SELECT COUNT(*) 
                    FROM post_tags pt
                    INNER JOIN posts p ON pt.post_id = p.id
                    WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL
                ) WHERE id = :tag_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':tag_id' => $tagId]);
    }
}

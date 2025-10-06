<?php

declare(strict_types=1);

namespace App\Application\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PostController extends BaseController
{
    /**
     * 取得所有貼文.
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
            
            $whereClause = implode(' AND ', $where);
            
            // 計算總數
            $countSql = "SELECT COUNT(*) as total FROM posts WHERE {$whereClause}";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();
            
            // 獲取資料
            $offset = ($page - 1) * $perPage;
            $sql = "SELECT p.id, p.title, p.content, p.status, p.user_id, p.created_at, p.updated_at,
                           u.username as author
                    FROM posts p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE {$whereClause} 
                    ORDER BY p.created_at DESC 
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
                $post['author'] = $post['author'] ?? 'Unknown';
                return $post;
            }, $posts);
            
            // 格式化回應
            $responseData = $this->paginatedResponse($posts, $total, $page, $perPage);
            $response->getBody()->write($responseData);
            
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $errorResponse = $this->errorResponse('Failed to fetch posts: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 取得單一貼文.
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $post = [
            'id' => $id,
            'title' => "貼文 #{$id}",
            'content' => "這是第 {$id} 篇貼文的內容",
            'created_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($post));

        return $response->withHeader('Content-Type', 'application/json');
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
                mt_rand(0, 0xffff)
            );
            
            // 獲取最新的 seq_number
            $seqStmt = $pdo->query('SELECT MAX(seq_number) as max_seq FROM posts');
            $maxSeq = $seqStmt->fetchColumn();
            $seqNumber = ($maxSeq ?? 0) + 1;
            
            // 插入新文章
            $sql = "INSERT INTO posts (uuid, seq_number, title, content, user_id, status, views, is_pinned, created_at) 
                    VALUES (:uuid, :seq_number, :title, :content, :user_id, :status, 0, 0, datetime('now'))";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uuid' => $uuid,
                ':seq_number' => $seqNumber,
                ':title' => $title,
                ':content' => $content,
                ':user_id' => $userId,
                ':status' => $status,
            ]);
            
            $postId = $pdo->lastInsertId();
            
            // 回傳新建立的文章
            $post = [
                'id' => (int) $postId,
                'uuid' => $uuid,
                'seq_number' => $seqNumber,
                'title' => $title,
                'content' => $content,
                'user_id' => $userId,
                'status' => $status,
                'created_at' => date('c'),
            ];
            
            $response->getBody()->write($this->successResponse($post, '貼文建立成功'));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $errorResponse = $this->errorResponse('建立文章失敗: ' . $e->getMessage());
            $response->getBody()->write($errorResponse);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * 更新貼文.
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $body = $request->getParsedBody();

        $post = [
            'id' => $id,
            'title' => $body['title'] ?? "更新的貼文 #{$id}",
            'content' => $body['content'] ?? '更新後的內容',
            'updated_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($post, '貼文更新成功'));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 刪除貼文.
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface
    {
        $result = [
            'deleted_id' => $id,
            'deleted_at' => date('c'),
        ];

        $response->getBody()->write($this->successResponse($result, '貼文刪除成功'));

        return $response->withHeader('Content-Type', 'application/json');
    }
}

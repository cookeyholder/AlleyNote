<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * 測試文章資料 Seed
 *
 * 建立一些測試用的文章資料，包含不同來源類型的文章，
 * 用於測試統計功能和來源追蹤功能。
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-04
 */
class TestPostsData extends AbstractSeed
{
    /**
     * Run Method.
     *
     * 建立測試用的文章資料，包含：
     * - 有完整來源資訊的文章
     * - 缺少來源資訊的舊文章（用於測試 migration）
     * - 不同來源類型的文章
     */
    public function run(): void
    {
        // 清除現有測試資料
        $this->execute('DELETE FROM posts WHERE title LIKE "Test Post%"');

        // 建立測試文章資料
        $posts = [
            // 有完整來源資訊的文章
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440001',
                'seq_number' => 1001,
                'title' => 'Test Post - Direct Access',
                'content' => 'This is a test post accessed directly.',
                'user_id' => 1,
                'user_ip' => '192.168.1.100',
                'views' => 150,
                'is_pinned' => false,
                'status' => 'published',
                'publish_date' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'source_type' => 'direct',
                'source_detail' => json_encode([
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'referer' => null,
                    'landing_page' => '/posts/1001'
                ]),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'updated_at' => null,
                'deleted_at' => null
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440002',
                'seq_number' => 1002,
                'title' => 'Test Post - Search Engine',
                'content' => 'This post was found via search engine.',
                'user_id' => 2,
                'user_ip' => '192.168.1.101',
                'views' => 89,
                'is_pinned' => false,
                'status' => 'published',
                'publish_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'source_type' => 'search_engine',
                'source_detail' => json_encode([
                    'search_engine' => 'google',
                    'keywords' => 'test post example',
                    'search_position' => 3,
                    'referer' => 'https://www.google.com/search?q=test+post+example'
                ]),
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'updated_at' => null,
                'deleted_at' => null
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440003',
                'seq_number' => 1003,
                'title' => 'Test Post - Social Media',
                'content' => 'This post was shared on social media.',
                'user_id' => 1,
                'user_ip' => '192.168.1.102',
                'views' => 234,
                'is_pinned' => true,
                'status' => 'published',
                'publish_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'source_type' => 'social_media',
                'source_detail' => json_encode([
                    'platform' => 'twitter',
                    'post_id' => 'tweet_12345',
                    'shared_by' => '@testuser',
                    'referer' => 'https://t.co/abcd1234'
                ]),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => null,
                'deleted_at' => null
            ],
            // 模擬需要更新的舊文章（使用無效的來源類型來觸發更新）
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440004',
                'seq_number' => 1004,
                'title' => 'Test Post - Legacy Invalid Source',
                'content' => 'This is a legacy post with invalid source information.',
                'user_id' => 2,
                'user_ip' => '192.168.1.103',
                'views' => 67,
                'is_pinned' => false,
                'status' => 'published',
                'publish_date' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'source_type' => 'invalid_source', // 無效的來源類型，會被 migration 更新
                'source_detail' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
                'updated_at' => null,
                'deleted_at' => null
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440005',
                'seq_number' => 1005,
                'title' => 'Test Post - Legacy Empty Source',
                'content' => 'This is another legacy post with empty source.',
                'user_id' => 1,
                'user_ip' => '192.168.1.104',
                'views' => 45,
                'is_pinned' => false,
                'status' => 'published',
                'publish_date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'source_type' => '', // 空字串，會被 migration 更新
                'source_detail' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'updated_at' => null,
                'deleted_at' => null
            ]
        ];

        // 插入測試資料
        $this->table('posts')->insert($posts)->save();

        $this->output->writeln('<info>Test posts data created successfully!</info>');
        $this->output->writeln('<info>Created 5 test posts:</info>');
        $this->output->writeln('  - 3 posts with proper source information');
        $this->output->writeln('  - 2 legacy posts with invalid source information (for migration testing)');
    }
}

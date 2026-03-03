<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * 新增頁腳設定.
 */
final class AddFooterSettings extends AbstractMigration
{
    public function change(): void
    {
        $this->table('settings')->insert([
            [
                'key' => 'footer_copyright',
                'value' => '© 2024 AlleyNote. All rights reserved.',
                'type' => 'string',
                'description' => '頁腳版權文字',
            ],
            [
                'key' => 'footer_description',
                'value' => '基於 Domain-Driven Design 的企業級公布欄系統',
                'type' => 'string',
                'description' => '頁腳描述文字',
            ],
        ])->saveData();
    }
}

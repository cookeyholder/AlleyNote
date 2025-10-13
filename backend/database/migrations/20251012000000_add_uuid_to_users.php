<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUuidToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        
        // 檢查 uuid 欄位是否已存在
        if (!$table->hasColumn('uuid')) {
            $table->addColumn('uuid', 'string', [
                'limit' => 36,
                'null' => true,
                'after' => 'id'
            ])
            ->addIndex(['uuid'], ['unique' => true])
            ->update();
            
            // 為現有使用者生成 UUID
            $this->execute("
                UPDATE users 
                SET uuid = lower(hex(randomblob(4)) || '-' || hex(randomblob(2)) || '-4' || substr(hex(randomblob(2)), 2) || '-' || substr('89ab', abs(random()) % 4 + 1, 1) || substr(hex(randomblob(2)), 2) || '-' || hex(randomblob(6)))
                WHERE uuid IS NULL
            ");
            
            // 將 uuid 改為 NOT NULL
            $table->changeColumn('uuid', 'string', [
                'limit' => 36,
                'null' => false
            ])
            ->update();
        }
    }
}

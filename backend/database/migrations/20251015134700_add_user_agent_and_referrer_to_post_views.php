<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserAgentAndReferrerToPostViews extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('post_views');
        
        $table->addColumn('user_agent', 'text', [
            'null' => true,
            'after' => 'user_ip',
        ])
        ->addColumn('referrer', 'text', [
            'null' => true,
            'after' => 'user_agent',
        ])
        ->update();
    }
}

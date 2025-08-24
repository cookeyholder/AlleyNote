<?php

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds' => 'database/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => 'sqlite',
            'name' => 'database/alleynote.db',
            'charset' => 'utf8'
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'name' => 'database/test.db',
            'charset' => 'utf8'
        ],
        'production' => [
            'adapter' => 'sqlite',
            'name' => 'database/alleynote_production.db',
            'charset' => 'utf8'
        ]
    ],
    'version_order' => 'creation',
    'migration_base_class' => 'Phinx\Migration\AbstractMigration',
    'seed_base_class' => 'Phinx\Seed\AbstractSeed'
];

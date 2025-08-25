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
            'name' => 'database/alleynote',
            'charset' => 'utf8'
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'name' => 'database/test',
            'charset' => 'utf8'
        ],
        'production' => [
            'adapter' => 'sqlite',
            'name' => 'database/alleynote',
            'charset' => 'utf8'
        ]
    ],
    'version_order' => 'creation',
    'migration_base_class' => 'Phinx\Migration\AbstractMigration',
    'seed_base_class' => 'Phinx\Seed\AbstractSeed'
];

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
            'name' => 'database/alleynote.sqlite3',
            'charset' => 'utf8',
            'suffix' => ''
        ],
        'testing' => [
            'adapter' => 'sqlite',
            'name' => 'database/test.sqlite3',
            'charset' => 'utf8',
            'suffix' => ''
        ],
        'production' => [
            'adapter' => 'sqlite',
            'name' => 'database/alleynote.sqlite3',
            'charset' => 'utf8',
            'suffix' => ''
        ]
    ],
    'version_order' => 'creation',
    'migration_base_class' => 'Phinx\Migration\AbstractMigration',
    'seed_base_class' => 'Phinx\Seed\AbstractSeed'
];

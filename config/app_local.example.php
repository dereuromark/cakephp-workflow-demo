<?php
/**
 * Local configuration for the cakephp-workflow demo.
 *
 * Defaults to SQLite so the demo runs with zero database setup. Copy this file to
 * `config/app_local.php` (the Makefile does this for you) and adjust if you prefer MySQL.
 */
return [
    'debug' => filter_var(env('DEBUG', true), FILTER_VALIDATE_BOOLEAN),

    'Security' => [
        'salt' => env('SECURITY_SALT', 'demo-only-insecure-salt-change-me-1234567890abcdef'),
    ],

    'Datasources' => [
        'default' => [
            'className' => \Cake\Database\Connection::class,
            'driver' => \Cake\Database\Driver\Sqlite::class,
            'database' => ROOT . DS . 'tmp' . DS . 'demo.sqlite',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,

            // --- Prefer MySQL? Comment the SQLite lines above and use: ---
            // 'driver' => \Cake\Database\Driver\Mysql::class,
            // 'host' => '127.0.0.1', 'username' => 'root', 'password' => '',
            // 'database' => 'workflow_demo', 'encoding' => 'utf8mb4',
        ],
        'test' => [
            'className' => \Cake\Database\Connection::class,
            'driver' => \Cake\Database\Driver\Sqlite::class,
            'database' => ROOT . DS . 'tmp' . DS . 'demo_test.sqlite',
            'timezone' => 'UTC',
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
        ],
    ],

    'EmailTransport' => [
        'default' => ['className' => \Cake\Mailer\Transport\DebugTransport::class],
    ],

    // Tell the workflow plugin where to scan for attribute-defined state machines.
    'Workflow' => [
        'loader' => [
            'namespaces' => ['App\\Workflow'],
        ],
    ],
];

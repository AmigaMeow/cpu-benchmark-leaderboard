<?php
// Copy to config/database.php (or set the DB_* env vars). config/database.php
// is gitignored — never commit real credentials.
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'leaderboard',
    'username' => getenv('DB_USER') ?: 'leaderboard',
    'password' => getenv('DB_PASS') ?: 'leaderboard',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

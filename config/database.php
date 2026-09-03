<?php
/**
 * Ma'lumotlar bazasi konfiguratsiyasi.
 *
 * Drayver almashtirgichi (driver switch): bir xil migratsiyalar va ilova
 * kodi sqlite (dev), pgsql yoki mysql (production) bilan ishlashi uchun.
 * DB qatlami ushbu konfiguratsiyani o'qiydi va mos DSN quradi.
 */

$root = dirname(__DIR__);

return [
    // Faol drayver: sqlite | pgsql | mysql
    'default' => getenv('DB_DRIVER') ?: 'sqlite',

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => getenv('DB_DATABASE') ?: $root . '/database/database.sqlite',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_DATABASE') ?: 'adpi_monitoring',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],

        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'adpi_monitoring',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
        ],
    ],
];

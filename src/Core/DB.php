<?php

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * PDO ulanish abstraksiyasi (singleton).
 *
 * config/database.php'ni o'qiydi va drayver almashtirgichi orqali
 * sqlite (dev) / pgsql / mysql uchun bir xil interfeys beradi.
 * Barcha so'rovlar tayyorlangan (prepared) statements orqali bajariladi.
 */
final class DB
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = Config::get('database.default', 'sqlite');
        $cfg = Config::get("database.connections.$driver");

        if (!is_array($cfg)) {
            throw new \RuntimeException("Ma'lumotlar bazasi ulanishi topilmadi: $driver");
        }

        [$dsn, $user, $pass] = self::buildDsn($cfg);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);

        if ($cfg['driver'] === 'sqlite') {
            // Portativ bo'lsa-da, referens yaxlitligi uchun FK'ni yoqamiz.
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }

        return self::$pdo;
    }

    /**
     * @return array{0:string,1:?string,2:?string} [dsn, username, password]
     */
    private static function buildDsn(array $cfg): array
    {
        switch ($cfg['driver']) {
            case 'sqlite':
                $path = $cfg['database'];
                if ($path !== ':memory:') {
                    $dir = dirname($path);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                }
                return ["sqlite:$path", null, null];

            case 'pgsql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['database']
                );
                return [$dsn, $cfg['username'] ?? null, $cfg['password'] ?? null];

            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $cfg['host'],
                    $cfg['port'],
                    $cfg['database'],
                    $cfg['charset'] ?? 'utf8mb4'
                );
                return [$dsn, $cfg['username'] ?? null, $cfg['password'] ?? null];

            default:
                throw new \RuntimeException("Qo'llab-quvvatlanmaydigan drayver: {$cfg['driver']}");
        }
    }

    public static function driver(): string
    {
        return Config::get('database.default', 'sqlite');
    }

    /**
     * Tayyorlangan so'rovni bajaradi va statement qaytaradi.
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        return self::run($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', $placeholders)
        );
        self::run($sql, $data);
        return (int) self::connection()->lastInsertId();
    }

    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connection()->commit();
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    /**
     * Test / migrate:fresh uchun ulanishni tiklaydi.
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}

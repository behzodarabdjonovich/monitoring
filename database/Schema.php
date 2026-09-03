<?php

namespace App\Database;

use App\Core\DB;

/**
 * Portativ migratsiya yordamchisi.
 *
 * Migratsiyalar sxemani neytral (driver-agnostic) tarzda ta'riflaydi;
 * drayverga xos farqlar (autoincrement PK, BOOLEAN, TIMESTAMP) shu yerda
 * hal qilinadi, shunda bir xil migratsiyalar sqlite / pgsql / mysql'ga
 * mos keladi.
 */
final class Blueprint
{
    private string $table;
    /** @var string[] */
    private array $columns = [];
    /** @var string[] */
    private array $constraints = [];
    /** @var string[] Alohida bajariladigan indeks/DDL so'rovlari */
    private array $extra = [];
    private string $driver;

    public function __construct(string $table, string $driver)
    {
        $this->table = $table;
        $this->driver = $driver;
    }

    public function id(string $name = 'id'): self
    {
        switch ($this->driver) {
            case 'pgsql':
                $this->columns[] = "$name SERIAL PRIMARY KEY";
                break;
            case 'mysql':
                $this->columns[] = "$name INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY";
                break;
            default: // sqlite
                $this->columns[] = "$name INTEGER PRIMARY KEY AUTOINCREMENT";
        }
        return $this;
    }

    public function integer(string $name, bool $nullable = true, ?int $default = null): self
    {
        $this->columns[] = trim("$name INTEGER" . $this->nullSql($nullable) . $this->defaultSql($default));
        return $this;
    }

    public function text(string $name, bool $nullable = true, ?string $default = null): self
    {
        $type = $this->driver === 'mysql' ? 'TEXT' : 'TEXT';
        $this->columns[] = trim("$name $type" . $this->nullSql($nullable) . $this->defaultSql($default));
        return $this;
    }

    public function string(string $name, int $length = 255, bool $nullable = true, ?string $default = null): self
    {
        // Portativ: VARCHAR(n) hamma drayverda ishlaydi.
        $this->columns[] = trim("$name VARCHAR($length)" . $this->nullSql($nullable) . $this->defaultSql($default));
        return $this;
    }

    public function real(string $name, bool $nullable = true, ?float $default = null): self
    {
        $type = $this->driver === 'pgsql' ? 'DOUBLE PRECISION' : ($this->driver === 'mysql' ? 'DOUBLE' : 'REAL');
        $this->columns[] = trim("$name $type" . $this->nullSql($nullable) . ($default !== null ? " DEFAULT $default" : ''));
        return $this;
    }

    public function boolean(string $name, bool $nullable = false, bool $default = false): self
    {
        // Portativlik uchun BOOLEAN'ni INTEGER 0/1 sifatida ifodalaymiz.
        if ($this->driver === 'pgsql') {
            $def = $default ? 'TRUE' : 'FALSE';
            $this->columns[] = trim("$name BOOLEAN" . $this->nullSql($nullable) . " DEFAULT $def");
        } else {
            $def = $default ? '1' : '0';
            $this->columns[] = trim("$name INTEGER" . $this->nullSql($nullable) . " DEFAULT $def");
        }
        return $this;
    }

    public function date(string $name, bool $nullable = true): self
    {
        $this->columns[] = trim("$name DATE" . $this->nullSql($nullable));
        return $this;
    }

    public function timestamp(string $name, bool $nullable = true): self
    {
        $type = $this->driver === 'sqlite' ? 'TIMESTAMP' : 'TIMESTAMP';
        $this->columns[] = trim("$name $type" . $this->nullSql($nullable));
        return $this;
    }

    /**
     * created_at + updated_at ustunlari.
     */
    public function timestamps(bool $withUpdated = true): self
    {
        $this->timestamp('created_at');
        if ($withUpdated) {
            $this->timestamp('updated_at');
        }
        return $this;
    }

    public function foreign(string $column, string $refTable, string $refColumn = 'id'): self
    {
        $this->constraints[] = "FOREIGN KEY ($column) REFERENCES $refTable($refColumn)";
        return $this;
    }

    public function unique(array $columns): self
    {
        $this->constraints[] = 'UNIQUE (' . implode(', ', $columns) . ')';
        return $this;
    }

    public function index(array $columns): self
    {
        $cols = implode('_', $columns);
        $colList = implode(', ', $columns);
        $this->extra[] = "CREATE INDEX IF NOT EXISTS idx_{$this->table}_{$cols} ON {$this->table} ($colList)";
        return $this;
    }

    /**
     * @return string[] Bajariladigan DDL so'rovlari (CREATE TABLE + indekslar).
     */
    public function toSql(): array
    {
        $parts = array_merge($this->columns, $this->constraints);
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (\n  " . implode(",\n  ", $parts) . "\n)";
        return array_merge([$sql], $this->extra);
    }

    private function nullSql(bool $nullable): string
    {
        return $nullable ? '' : ' NOT NULL';
    }

    private function defaultSql(mixed $default): string
    {
        if ($default === null) {
            return '';
        }
        if (is_string($default)) {
            return " DEFAULT '" . str_replace("'", "''", $default) . "'";
        }
        return ' DEFAULT ' . $default;
    }
}

final class Schema
{
    public static function create(string $table, callable $callback): void
    {
        $driver = DB::driver();
        $blueprint = new Blueprint($table, $driver);
        $callback($blueprint);
        foreach ($blueprint->toSql() as $sql) {
            DB::connection()->exec($sql);
        }
    }

    public static function dropIfExists(string $table): void
    {
        DB::connection()->exec("DROP TABLE IF EXISTS $table");
    }
}

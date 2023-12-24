<?php

namespace Energy;

use PDOStatement;
use Energy\Core\Database\Database;

class Db
{
    private static $db = false;

    public static function use()
    {
        if (!self::$db) {
            self::$db = new Database([
                'type'     => env('DB_TYPE'),
                'host'     => env('DB_HOST'),
                'database' => env('DB_NAME'),
                'username' => env('DB_USER'),
                'password' => env('DB_PASSWORD'),
                'port'     => env('DB_PORT'),
                'charset'  => env('DB_CHARSET'),
                'prefix'   => env('DB_PREFIX'),
            ]);

            Hooks::add('Kernel::launch.destructor', function () {
                if (isset(self::$db) && self::$db)
                    self::$db = false;
            });
        }

        return self::$db;
    }

    public static function raw(string $string, array $map = [])
    {
        return Database::raw($string, $map);
    }

    public static function query(string $statement, array $map = []): ?PDOStatement
    {
        return self::use()->query($statement, $map);
    }

    public static function quote(string $string): string
    {
        return self::use()->quote($string);
    }

    public static function tableQuote(string $table): string
    {
        return self::use()->tableQuote($table);
    }

    public static function columnQuote(string $column): string
    {
        return self::use()->columnQuote($column);
    }

    public static function create(string $table, $columns, $options = null): ?PDOStatement
    {
        return self::use()->create($table, $columns, $options);
    }

    public static function drop(string $table): ?PDOStatement
    {
        return self::use()->drop($table);
    }

    public static function select(string $table, $join, $columns = null, $where = null): ?array
    {
        return self::use()->select($table, $join, $columns, $where);
    }

    public static function insert(string $table, array $values, string $primaryKey = null): ?PDOStatement
    {
        return self::use()->insert($table, $values, $primaryKey);
    }

    public static function update(string $table, $data, $where = null): ?PDOStatement
    {
        return self::use()->update($table, $data, $where);
    }

    public static function delete(string $table, $where): ?PDOStatement
    {
        return self::use()->delete($table, $where);
    }

    public static function replace(string $table, array $columns, $where = null): ?PDOStatement
    {
        return self::use()->replace($table, $columns, $where);
    }

    public static function get(string $table, $join = null, $columns = null, $where = null)
    {
        return self::use()->get($table, $join, $columns, $where);
    }

    public static function has(string $table, $join, $where = null): bool
    {
        return self::use()->has($table, $join, $where);
    }

    public static function rand(string $table, $join = null, $columns = null, $where = null): array
    {
        return self::use()->rand($table, $join, $columns, $where);
    }

    public static function count(string $table, $join = null, $column = null, $where = null): ?int
    {
        return self::use()->count($table, $join, $column, $where);
    }

    public static function avg(string $table, $join, $column = null, $where = null): ?string
    {
        return self::use()->avg($table, $join, $column, $where);
    }

    public static function max(string $table, $join, $column = null, $where = null): ?string
    {
        return self::use()->max($table, $join, $column, $where);
    }

    public static function min(string $table, $join, $column = null, $where = null): ?string
    {
        return self::use()->min($table, $join, $column, $where);
    }

    public static function sum(string $table, $join, $column = null, $where = null): ?string
    {
        return self::use()->sum($table, $join, $column, $where);
    }

    public static function action(callable $actions)
    {
        self::use()->action($actions);
    }

    public static function id(string $name = null): ?string
    {
        return self::use()->id($name);
    }

    public static function debug()
    {
        return self::use()->debug();
    }

    public static function beginDebug()
    {
        self::use()->beginDebug();
    }

    public static function debugLog()
    {
        return self::use()->debugLog();
    }

    public static function last(): ?string
    {
        return self::use()->last();
    }

    public static function log()
    {
        return self::use()->log();
    }

    public static function info()
    {
        return self::use()->info();
    }

    public static function whereClause($where, array &$map): string
    {
        return self::use()->whereClause($where, $map);
    }

    public static function prepareMapData(array $map): array
    {
        return self::use()->prepareMapData($map);
    }

    public static function buildJoin(string $table, array $join, array &$map): string
    {
        return self::use()->buildJoin($table, $join, $map);
    }

    public static function selectContext(string $table, array &$map, $join, &$columns = null, $where = null, $columnFn = null): string
    {
        return self::use()->selectContext($table, $map, $join, $columns, $where, $columnFn);
    }
}

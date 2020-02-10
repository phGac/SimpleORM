<?php

namespace Otter\ORM;

/**
 * Simple ORM for SQL Server by Philippe Gac
 */
class SimpleORM {

    public static $schemas = [];
    public static $connection = null;
    public static $lastQuery = null;
    public static $lastQueryErrorInfo = null;

    private static $modelsPath;

    public function __construct(string $host, string $database, string $user, string $password) {
        $db = new \Otter\ORM\DataBase();
        $db->configure($host, $database, $user, $password);
        self::$connection = $db->connect();
    }

    /**
     * Load and configure schemas
     */
    public function schemas(string $modelsPath) {
        if ($handle = opendir($modelsPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $schema = require_once($modelsPath.'/'.$entry);
                    self::$schemas[$schema::$modelName] = $schema;
                }
            }
            closedir($handle);
        }
    }

    /**
     * A optional function.
     * Generates views that will be used in queries.
     * Recommended.
     */
    public function generateViews() {}

    public static function lastQuery() {
        return self::$lastQuery;
    }

    public static function lastQueryErrorInfo() {
        return self::$lastQueryErrorInfo;
    }

    public static function get(string $modelName): ?Model {
        if(! array_key_exists($modelName, self::$schemas)) {
            return null;
        } else {
            return new Model(self::$schemas[$modelName]);
        }
    }

    public static function db(string $sqlQuery, array $columns = []) {
        self::$lastQuery = $sqlQuery;
        return QueryRow::execute($sqlQuery, $columns);
    }

}
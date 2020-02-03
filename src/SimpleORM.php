<?php

namespace Otter\ORM;

/**
 * Simple ORM for SQL Server by Philippe Gac
 */
class SimpleORM {

    public static $schemas;
    public static $connection;
    public static $lastQuery;
    public static $lastQueryErrorInfo;

    private static $modelsPath;

    public function __construct(string $host, string $database, string $user, string $password) {
        $db = new \Otter\ORM\DataBase();
        $db->configure($host, $database, $user, $password);
        self::$connection = $db->connect();
    }

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

    public static function lastQuery() {
        return self::$lastQuery;
    }

    public static function lastQueryErrorInfo() {
        return self::$lastQueryErrorInfo;
    }

    /**
     * Get a model.
     * @param {string} modelName
     * @return Model
     */
    public static function get(string $modelName): ?Model {
        if(! array_key_exists($modelName, self::$schemas)) {
            return null;
        } else {
            return new Model(self::$schemas[$modelName]);
        }
    }

}
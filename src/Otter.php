<?php

namespace Otter\ORM;

use stdClass;
use Otter\ORM\Schema\Schema;

/**
 * Simple ORM for SQL Server by Philippe Gac
 */
class Otter {
    public static $schemas = [];
    public static $connection = null;
    public static $lastQuery = null;
    public static $lastQueryErrorInfo = null;

    public static $schemasPath = null;

    public function __construct(string $host, string $database, string $user, string $password) {
        $db = new \Otter\ORM\DataBase();
        $db->configure($host, $database, $user, $password);
        self::$connection = $db->connect();
    }

    /**
     * Load and configure schemas
     */
    public function schemas(string $schemasPath): void {
        if ($handle = opendir($schemasPath)) {
            self::$schemasPath = $schemasPath;
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $schema = explode('Schema', $entry);
                    self::$schemas[$schema[0]] = null;
                }
            }
            closedir($handle);
        } else {
            throw new Exception("Incorrect path.", 1);
        }
    }

    public static function get(string $modelName): ?Model {
        if(! array_key_exists($modelName, self::$schemas)) {
            return null;
        } else {
            if(self::$schemas[$modelName] === null) {
                $path = self::$schemasPath.'/'.$modelName.'Schema.xml';
                $schemaXML = simplexml_load_file($path);
                $schema = new Schema($modelName, $schemaXML);
                self::$schemas[$modelName] = $schema;                
            }

            return new Model( self::$schemas[$modelName] );
        }
    }

    public static function getSchema(string $modelName): ?Schema {
        return ( self::get($modelName) !== null ) ? self::$schemas[$modelName] : null;
    }

    public static function db(string $sqlQuery, array $params = []): ?array {
        return (QueryRunner::execute($sqlQuery, $params));
    }

}

abstract class OtterValue {
    public const UNDEFINED = "OTTER-VALUE--UNDEFINED";
    public const DEFINED = "OTTER-VALUE--DEFINED";

    public const DATE_NOW = "GETDATE()";
    public const DATE_UTC_NOW = "GETUTCDATE()";

    public static function INTEGER($value) {
        return (int) $value;
    }

    public static function BOOLEAN($value) {
        if(in_array($value, ['FALSE', 'F', 'false', 'f', '0', 0, false])) {
            return false;
        } else {
            return true;
        }
    }

    public static function OR(string $column, ...$values) {
        $vals = ['OR' => []];
        foreach ($values as $value) {
            $vals['OR'][] = $value;
        }
        return [ $column => $vals];
    }

    public static function AND(string $column, ...$values) {
        $vals = ['AND' => []];
        foreach ($values as $value) {
            $vals['AND'][] = $value;
        }
        return [ $column => $vals];
    }
}

abstract class OtterDefaultValue {
    public const OTTER_DATE_NOW = "otter.date.now";
    public const OTTER_DATE_UTC_NOW = "otter.date-utc.now";
}

final class OtterResult {
    public $affectedRows = 0;
    public $objectsCount = 0;
    public $data = null;
    public $error = null;
}

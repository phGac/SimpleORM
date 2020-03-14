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
        return (QueryRunner::execute($sqlQuery, $params, true, true));
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
        if(in_array($value, ['FALSE', 'F', 'false', 'f', '0', 0, false], true)) {
            return false;
        } else {
            return true;
        }
    }

    public static function IN(string $column, ...$values) {
        $vals = ['IN' => []];
        foreach ($values as $value) {
            $vals['IN'][] = $value;
        }
        return [ $column => $vals];
    }

    public static function BETWEEN(string $column, int $min, int $max) {
        return [ $column => [ 'BETWEEN' => [$min, $max] ]];
    }

    public const EQUAL = '=';
    public const LESS_THAN = '<';
    public const MORE_THAN = '>';
    public const LESS_OR_EQUAL_THAN = '<=';
    public const MORE_OR_EQUAL_THAN = '>=';
    public const LIKE = 'LIKE';

    public static function WHEN($value, $result) {
        return ['WHEN', $value, $result];
    }

    public static function ELSE($result) {
        return ['ELSE', $result];
    }

}

abstract class OtterWhere {
    public static function condition(string $column, $value, string $comparation = '='): array {
        switch(gettype($value)) {
            case 'string':
                $value = "'$value'";
            break;
            case 'boolean':
                $value = ($value === false) ? 0 : 1;
            break;
        }

        [$objectName, $columnName] = \Otter\ORM\Query\getObjectNameAndColumnName($column);

        return [
            'as' => 'CONDITION',
            'special' => false,
            'column' => "[$objectName].[$columnName]",
            'value' => $value,
            'comparation' => $comparation,
        ];
    }

    public static function IN(string $column, ...$values): array {
        $toString = [];
        foreach ($values as $value) {
            switch(gettype($value)) {
                case 'string':
                    $value = "'$value'";
                break;
                case 'boolean':
                    $value = ($value === false) ? 0 : 1;
                break;
            }

            $toString[] = $value;
        }
        [$objectName, $columnName] = \Otter\ORM\Query\getObjectNameAndColumnName($column);
        $string = "[$objectName].[$columnName] IN(".implode(', ', $toString).')';

        return [
            'as' => 'CONDITION',
            'special' => true,
            'string' => $string,
        ];
    }

    public static function BETWEEN(string $column, int $min, int $max): array {
        [$objectName, $columnName] = \Otter\ORM\Query\getObjectNameAndColumnName($column);
        $string = "[$objectName].[$columnName] BETWEEN $min AND $max";

        return [
            'as' => 'CONDITION',
            'special' => true,
            'string' => $string,
        ];
    }

    public static function OR(array ...$conditions): array {
        $toString = self::union_conditions($conditions);

        $conditions_string = '('.implode(' OR ', $toString).')';

        return [
            'as' => 'UNION-CONDITIONS',
            'conditions' => $conditions_string,
        ];
    }

    public static function AND(array ...$conditions): array {
        $toString = self::union_conditions($conditions);

        $conditions_string = '('.implode(' AND ', $toString).')';

        return [
            'as' => 'UNION-CONDITIONS',
            'conditions' => $conditions_string,
        ];
    }

    private static function union_conditions(array $conditions): array {
        $toString = [];
        foreach ($conditions as $key => $condition) {
            switch($condition['as']) {
                case 'CONDITION':
                    \extract($condition);
                    if(! $special)
                        $toString[] = "($column $comparation $value)";
                    else 
                        $toString[] = "($string)";
                break;

                case 'UNION-CONDITIONS':
                    $toString[] = $condition['conditions'];
                break;

                default:
                    //error
                break;
            }

        }
        return $toString;
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

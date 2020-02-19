<?php

namespace Otter\ORM;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;

class QueryRunner {

    public static function execute(string $sql, array $valuesToprepare = [], bool $generateObjects = false, bool $asArray = false, bool $onlyReturnData = true) {
        Otter::$lastQuery = $sql;
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
        $stmt->execute($valuesToprepare);

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return self::otterResult($stmt);
        } else {
            return self::otterResult($onlyReturnData, $stmt, $generateObjects, null, $asArray, false);
        }
    }

    public static function executeWithJoins(Schema $schema, string $sql, array $valuesToprepare = [], bool $generateObjects = false, bool $asArray = false, bool $onlyReturnData = true) {
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
        $stmt->execute($valuesToprepare);

        Otter::$lastQuery = $sql;

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return self::otterResult($onlyReturnData, $stmt);
        } else {
            return self::otterResult($onlyReturnData, $stmt, $generateObjects, $schema, $asArray, true);
        }
    }

    public static function count(string $sql, array $columns = []): ?int {
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return null;
        } else {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count = (int) $row['TOTAL'];
            return $count;
        }
        
    }

    private static function otterResult(bool $onlyReturnData, $stmt, bool $generateObjects = false, $schema = null, bool $asArray = false, bool $hasInner = false) {
        $data = null;
        if($generateObjects) {
            if($asArray) {
                $data = self::generateObjects($stmt, $schema, $hasInner);
            } else {
                $data = ($stmt->fetchObject(PlainModel::class));
            }
        }
        if($onlyReturnData) {
            return $data;
        }
        
        $otterResult = new OtterResult();
        $otterResult->affectedRows = $stmt->rowCount();
        $otterResult->error = ($stmt->errorCode() !== '00000') ? $stmt->errorInfo() : null;
        $otterResult->data = $data;
        if($otterResult->data === null) {
            $otterResult->objectsCount = 0;
        } else {
            if(is_array($otterResult->data)) {
                $otterResult->objectsCount = count($otterResult->data);
            } else {
                $otterResult->objectsCount = 1;
            }
        }
        return $otterResult;
    }

    private static function generateObjects($stmt, $schema, bool $hasInner = false) {
        if(! $hasInner) {
            $objects = [];
            while( ( $object = ($stmt->fetchObject(PlainModel::class)) ) != null ){
                $objects[] = $object;
            }
            return $objects;
        } else {
            $objects = [];
            $objectsPositions = [];
            $i = 0;
            $primaryKey = $schema->pk;
            $associations = $schema->associations;
            while( ( $object = ($stmt->fetchObject(PlainModel::class)) ) != null ){
                if(! array_key_exists($object->$primaryKey, $objectsPositions) ) {
                    $objects[] = $object;
                    $objectsPositions[$object->$primaryKey] = $i;
                    $i++;
                }

                $objectKeys = array_keys((array)$object);
                $position = $objectsPositions[$object->$primaryKey];
                foreach ($associations as $key => $value) {
                    $matches = \preg_grep("/$key\./", $objectKeys);
                    if(\count($matches) > 0) {
                        $dataObjectAssociation = [];
                        foreach ($matches as $match) {
                            $e = \explode('.', $match);
                            if(count($e) > 1) {
                                $associationName = $e[0];
                                $column = $e[1];
                                //$object->$associationName[$column] = $v;
                                $dataObjectAssociation[$column] = $object->$match;
                                unset($object->{"$associationName.$column"});
                            }
                        }
                        switch(\strtolower($value->type)) {
                            case SchemaAssociation::HasOne:
                                $objects[$position]->$associationName = $dataObjectAssociation;
                            break;
                            case SchemaAssociation::BelongsTo:
                                $objects[$position]->$associationName = $dataObjectAssociation;
                            break;
                            case SchemaAssociation::HasMany:
                                $objects[$position]->$associationName[] = $dataObjectAssociation;
                            break;
                            case SchemaAssociation::BelongsToMany:
                                $objects[$position]->$associationName[] = $dataObjectAssociation;
                            break;
                        }
                    }
                }
            }
            return $objects;
        }
    }

}
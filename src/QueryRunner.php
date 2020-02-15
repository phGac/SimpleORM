<?php

namespace Otter\ORM;

use Otter\ORM\Schema\SchemaAssociation;

class QueryRunner {

    public static function execute(string $sql, array $columns = [], bool $returnBoolean = false) {
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return (! $returnBoolean) ? null : false;
        } else {
            if($returnBoolean) {
                return true;
            }
            
            $objects = [];
            while( ( $object = ($stmt->fetchObject(PlainModel::class)) ) != null ){
                $objects[] = $object;
            }
            return $objects;
        }
    }

    public static function executeWithJoins($schema, string $sql, array $columns = []): ?array {
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return null;
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

    public static function count(string $sql, array $columns = []): ?int {
        $conn = Otter::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $conn->errorInfo();
            Otter::$lastQueryErrorInfo = $info;
            return null;
        } else {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count = (int) $row['COUNT'];
            return $count;
        }
        
    }

}
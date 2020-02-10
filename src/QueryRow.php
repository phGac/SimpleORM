<?php

namespace Otter\ORM;

class QueryRow {

    public static function execute(string $sql, array $columns = [], bool $plainObjects = true): ?array {
        $conn = SimpleORM::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $conn->errorInfo();
            SimpleORM::$lastQueryErrorInfo = $info;
            return null;
        } else {
            $objects = [];
            $class = ($plainObjects) ? PlainModel::class : Model::class;
            while( ( $object = ($stmt->fetchObject($class)) ) != null ){
                $objects[] = $object;
            }
            return $objects;
        }
    }

    public static function executeWithJoins(string $schema, string $sql, array $columns = [], bool $plainObjects = true): ?array {
        $conn = SimpleORM::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $stmt->errorInfo();
            SimpleORM::$lastQueryErrorInfo = $info;
            return null;
        } else {
            $associations = $schema::$associations;
            $primaryKey = 'id';
            foreach ($schema::$columns as $key => $value) {
                if(isset($value['primaryKey']) && $value['primaryKey']) {
                    $primaryKey = $key;
                }
            }

            $objects = [];
            $objectsPositions = [];
            $class = ($plainObjects) ? PlainModel::class : Model::class;
            $i = 0;
            while( ( $object = ($stmt->fetchObject($class)) ) != null ){
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
                        switch($value['type']) {
                            case ModelAssociation::HasOne:
                                $objects[$position]->$associationName = $dataObjectAssociation;
                            break;
                            case ModelAssociation::BelongsTo:
                                $objects[$position]->$associationName = $dataObjectAssociation;
                            break;
                            case ModelAssociation::HasMany:
                                $objects[$position]->$associationName[] = $dataObjectAssociation;
                            break;
                            case ModelAssociation::BelongsToMany:
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
        $conn = SimpleORM::$connection;
        $stmt = $conn->prepare($sql);
        $stmt->execute($columns);

        if ($stmt->errorCode() !== '00000'){
            $info = $conn->errorInfo();
            SimpleORM::$lastQueryErrorInfo = $info;
            return null;
        } else {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count = (int) $row['COUNT'];
            return $count;
        }
        
    }

}
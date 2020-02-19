<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\QueryRunner;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryUpdate;

class Update {

    protected $schema;
    protected $query;
    protected $valuesToPrepare;

    public function __construct(Schema $schema, array $columns) {
        $this->schema = $schema;
        $this->query = new QueryUpdate($schema->table);
        $this->valuesToPrepare = [];
        $this->setColumns($columns);
    }

    public function end(bool $onlyReturnResult = true) {
        $sql = \Otter\ORM\Maker\Query\QueryMakerUpdate::make($this->query);
        $result = QueryRunner::execute($sql, $this->valuesToPrepare, false, false, false);
        if(! $onlyReturnResult) {
            return $result;
        } else {
            return ($result->affectedRows !== -1) ? true : false;
        }
    }

    private function setColumns(array $columns): void {
        $columnsToSet = [];
        $valuesToPrepare = [];
        foreach ($this->schema->columns as $key => $column) {
            if(! array_key_exists($key, $columns)) {
                // error
            } else { // exists
                $columnsToSet[] = "$key = :$key";
                $valuesToPrepare[":$key"] = $columns[$key];
            }
        }

        $this->query->columnsToSet = $columnsToSet;
        $this->valuesToPrepare = $valuesToPrepare;
    }

    public function where(array $where): Delete {
        $whereArray = [];
        foreach ($where as $objectNameAndColumnName => $whereValue) {
            if(\gettype($objectNameAndColumnName) !== 'integer') {
                [$objectName, $columnName] = $this->getObjectNameAndColumnName($objectNameAndColumnName);
                
                if(\gettype($whereValue) === 'array') {
                    $simbol = \strtoupper($whereValue[0]);
                    $whereValue = $whereValue[1];
                    $whereArray[] = "[$objectName].[$columnName] $simbol :$objectName$columnName";
                    $whereArray[] = 'AND';
                } else if($whereValue !== null) {
                    $whereArray[] = "[$objectName].[$columnName] = :$objectName$columnName";
                    $whereArray[] = 'AND';
                }
            } else {
                switch(\gettype($whereValue)) {
                    case 'string':
                        switch(\strtoupper($whereValue)) {
                            case 'OR': 
                                $position = (count($whereArray)-1);
                                $whereArray[$position] = 'OR';
                            break;
                        }
                        continue;
                    break;
                    case 'array':
                        [$objectName, $columnName] = $this->getObjectNameAndColumnName($whereValue);
                        $values = (array_key_exists($columnName, $whereValue)) ? $whereValue[$columnName] : $whereValue["$objectName.$columnName"];
                        $i = 65; // ASCII CODE for 'A'
                        foreach ($values as $key => $val) {
                            foreach ($val as $v) {
                                $ascii = chr($i);
                                $whereArray[] = "[$objectName].[$columnName] = :$objectName$columnName$ascii";
                                $whereArray[] = $key; // OR, AND
                                $i++;

                                if($objectName !== $this->schema->name) {
                                    if(array_key_exists($objectName, $this->schema->associations)) {
                                        $type = $this->associationsSchemas[$objectName]->columns[$columnName]->type;
                                        $v = $this->valueToSQL($type, $v);
                                    }
                                } else {
                                    $type = $this->schema->columns[$columnName]->type;
                                    $v = $this->valueToSQL($type, $v);
                                }
                    
                                $this->valuesToPrepare[":$objectName$columnName$ascii"] = $v;
                            }
                            array_pop($whereArray);
                        }
                    continue 2;
                }
                
            }

            if($objectName !== $this->schema->name) {
                if(array_key_exists($objectName, $this->schema->associations)) {
                    $type = $this->associationsSchemas[$objectName]->columns[$columnName]->type;
                    $whereValue = $this->valueToSQL($type, $whereValue);
                }
            } else {
                $type = $this->schema->columns[$columnName]->type;
                $whereValue = $this->valueToSQL($type, $whereValue);
            }

            $this->valuesToPrepare[":$objectName$columnName"] = $whereValue;
            array_pop($whereArray); // remove last 'OR' or last 'AND'.
        }
        $this->query->where = $whereArray;

        return $this;
    }

    /// CAMBIAR DE LUGAR!!
    protected function valueToSQL(string $columnType, $originalValue) {
        $value = $originalValue;
        switch($columnType) {
            case "boolean":
            $value = ($originalValue === true) ? 1 : 0;
            break;
            case null:
            $value = "NULL";
            break;
        }
        return $value;
    }

    protected function getObjectNameAndColumnName($objectNameAndColumnName) {
        if(\is_array($objectNameAndColumnName)) {
            $objectNameAndColumnName = (array_keys($objectNameAndColumnName)[0]);
        }

        $objectNameAndColumnName = explode('.', $objectNameAndColumnName);
        if(\count($objectNameAndColumnName) > 1) {
            $objectName = $objectNameAndColumnName[0];
            $columnName = $objectNameAndColumnName[1];
        } else {
            $objectName = $this->schema->name;
            $columnName = $objectNameAndColumnName[0];
        }
        return [
            $objectName,
            $columnName
        ];
    }
}
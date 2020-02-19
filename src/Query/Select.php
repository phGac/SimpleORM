<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\QueryRunner;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QuerySelect;

class Select {

    protected $schema;
    protected $query;
    protected $valuesToPrepare;

    public function __construct(Schema $schema, array $onlyColumns = []) {
        $this->schema = $schema;
        $this->query = new QuerySelect($schema->table, $schema->name, $onlyColumns);
        $this->valuesToPrepare = [];
    }

    public function end(bool $onlyReturnData = true) {
        $sql = \Otter\ORM\Maker\Query\QueryMakerSelect::make($this->schema, $this->query);
        $asArray = ($this->query->top !== 1) ? true : false;
        if(count($this->query->join) > 0) {
            return QueryRunner::executeWithJoins($this->schema, $sql, $this->valuesToPrepare, true, $asArray, $onlyReturnData);
        } else {
            return QueryRunner::execute($sql, $this->valuesToPrepare, true, $asArray, $onlyReturnData);
        }
    }

    public function top(int $top): Select {
        $this->query->top = $top;
        return $this;
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

    public function join(array $joins): Select {
        $addColumnsJoins = (count($this->query->onlySelect) === 0) ? true : false;
        $this->joinAux($joins, $addColumnsJoins);
        return $this;
    }

    protected function joinAux(array $joins, bool $addColumnsJoins) {
        $modelName = $this->schema->name;
        $schema = $this->schema;

        $last_association = null;
        $association = null;
        foreach ($joins as $join) {
            $e = \explode('.', $join);
            if(count($e) === 1) {
                $assc_model = $modelName;
                $assc_name = $e[0];
            } else {
                $assc_model = $e[0];
                $assc_name = $e[1];
            }

            if($last_association === null) {
                $association = $this->schema->associations[$assc_name];
            } else if($last_association->name !== $assc_name) {
                $schema = Otter::getSchema($last_association->model);
                $modelName = $last_association->name;
                $association = $schema->associations[$assc_name];
            }

            if( $association->model !== OtterValue::UNDEFINED && $schema->name !== $association->model) {
                $schema = Otter::getSchema($association->model);
            }

            $joinString = $this->joinString($schema, $association, $modelName, $assc_name);
            $this->query->join[] = $joinString;
            
            if($addColumnsJoins) {
                if(\strtolower($association->type) !== SchemaAssociation::BelongsToMany) {
                    $columnsJoins = array_map(function($key) use ($assc_name) {
                        return "[$assc_name].[$key] AS [$assc_name.$key]";
                    }, array_keys($schema->columns));
                } else {
                    $through = Otter::getSchema($association->through);
                    $throughBridge = $through->associations[$association->throughBridge];
                    $schemaJoin = (Otter::getSchema($throughBridge->model));

                    $columnsJoins = array_map(function($key) use ($assc_name) {
                        return "[$assc_name].[$key] AS [$assc_name.$key]";
                    }, array_keys($schemaJoin->columns));
                }
                
                array_push($this->query->select, ...$columnsJoins);
            }

            $last_association = $association;
        }
    }

    public function orderBy(array $orderBy): Select {
        $this->query->orderby = $orderBy;
        return $this;
    }

    public function pagination(int $pag, int $maxPerPag): Select {
        if($pag <= 0) {
            throw new QueryException("Pagination CANNOT be less than 0. Start at 1.", 1);
        }

        $offset = ($maxPerPag * ($pag-1));
        $limit = ($maxPerPag * $pag);

        $this->query->pagination = "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        return $this;
    }

    public function groupBy(array $groupBy): Select {
        $this->query->groupby = $groupBy;
        return $this;
    }

    protected function joinString($schema, $association, $modelName, $assc_name) {
        switch(\strtolower($association->type)) {
            case SchemaAssociation::HasOne:
                $tableNameJoin = $schema->table;
                $foreignKey = $association->foreignKey;
                $key = $association->key;

                if(! $association->strict) {
                    return "LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                } else {
                    return "INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                }
            break;
            case SchemaAssociation::HasMany:
                $tableNameJoin = $schema->table;
                $foreignKey = $association->foreignKey;
                $key = $association->key;

                if(! $association->strict) {
                    return "LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                } else {
                    return "INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                }
            break;
            case SchemaAssociation::BelongsTo:
                $tableNameJoin = $schema->table;
                $foreignKey = $association->foreignKey;
                $key = $association->key;

                if(! $association->strict) {
                    return "LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                } else {
                    return "INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                }
            break;
            case SchemaAssociation::BelongsToMany:
                $through = Otter::getSchema($association->through);
                $throughBridge = $through->associations[$association->throughBridge];

                $schemaJoin = (Otter::getSchema($throughBridge->model));
                $tableNameJoin = $schemaJoin->table;
                $throughTableName = $through->table;
                $throughModelName = $through->name;
                $throughForeignKey = $association->throughKey;
                $foreignKey = $association->foreignKey;
                $throughKey = $throughBridge->foreignKey;
                $key = $throughBridge->key;

                if(! $association->strict) {
                    return "LEFT JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]"
                         ." LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                } else {
                    return "INNER JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]"
                          ."INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                }
            break;
            default:
                throw new Exception("Unknow Association type ($association->type)", 1);
            break;
        }
    }

    private function columnsJoin(array $columnsJoin, string $associationName): array {
        $columns = [];
        foreach (array_keys($columnsJoin) as $key) {
            $columns[] = "$associationName.$key";
        }
        
        return $columns;
    }

    //// CAMBIAR DE LUGAR!! (a otra clase)
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
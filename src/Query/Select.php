<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\QueryRunner;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query;
use Otter\ORM\Query\QuerySelect;

class Select {

    protected $schema;
    protected $query;
    protected $valuesToPrepare;

    public function __construct(Schema $schema, $onlyColumns = []) {
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

    public function max(string $column, string $alias): Select {
        [$objectName, $columnName] = $this->getObjectNameAndColumnName($column);
        $this->query->functions['MAX'][$column] = "MAX([$objectName].[$columnName]) AS $alias";
        return $this;
    }

    public function min(string $column, string $alias): Select {
        [$objectName, $columnName] = $this->getObjectNameAndColumnName($column);
        $this->query->functions['MIN'][$column] = "MIN([$objectName].[$columnName]) AS $alias";
        return $this;
    }

    public function avg(string $column, string $alias, bool $distinct = false) {
        $this->query->functions['AVG'][$column] = $alias;
        return $this;
    }

    public function sum(string $column, string $alias, bool $distinct = false) {
        $this->query->functions['SUM'][$column] = $alias;
        return $this;
    }

    public function concat(string $alias, string ...$mix): Select {
        $concat = [];
        foreach ($mix as $column) {
            if(count(\explode('.', $column)) > 1) {
                [$objectName, $columnName] = $this->getObjectNameAndColumnName($column);
                $concat[] = "[$objectName].[$columnName]";
            } else {
                $concat[] = "'$column'";
            }
        }
        $concat_string = implode(', ', $concat);
        $this->query->functions['CONCAT'][] = "CONCAT($concat_string) AS $alias";
        return $this;
    }

    public function case(string $column, array $when, string $name): Select {
        [$objectName, $columnName] = $this->getObjectNameAndColumnName($column);
        $case = [];
        foreach ($when as $value) {
            switch (\strtoupper($value[0])) {
                case 'WHEN':
                    if(\gettype($value[1]) === 'boolean') {
                        $value[1] = ($value[1] === true) ? 1 : 0;
                    }
                    $case[] = "WHEN $value[1] THEN '$value[2]'";
                    break;
                case 'ELSE':
                    $case[] = "ELSE '$value[1]'";
                    break;
            }
        }

        $this->query->functions['CASE'][] = "CASE [$objectName].[$columnName] ".implode(' ', $case)." END AS $name";
        return $this;
    }

    public function rownumber(string $alias, string ...$orderBy): Select {
        $orders = [];
        foreach ($orderBy as $column) {
            [$objectName, $columnName] = $this->getObjectNameAndColumnName($column);
            $orders[] = "[$objectName].[$columnName]";
        }
        $orderBy_string = implode(', ', $orders);
        $this->query->functions['ROW_NUMBER'][0] = "ROW_NUMBER() OVER( ORDER BY $orderBy_string) AS $alias";
        return $this;
    }

    public function where(array $where): Select {
        if(! isset($where['as'])) {
            // error
        }

        switch($where['as']) {
            case 'CONDITION':
                \extract($condition);
                if(! $special) {
                    [$objectName, $columnName] = \Otter\ORM\Query\getObjectNameAndColumnName($column);
                    if($objectName === '::object::') {
                        $objectName = $this->schema->name;
                    }

                    $this->query->where = "($column $comparation $value)";
                }
                else {
                    $string = \str_replace('::object::', $this->schema->name, $string);
                    $this->query->where = "($string)";
                }
            break;
            
            case 'UNION-CONDITIONS':
                $string = \str_replace('::object::', $this->schema->name, $where['conditions']);
                $this->query->where = $string;
            break;

            default:
                //error
        }

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

                if($last_association !== null && $last_association->name !== $assc_name) {
                    $association = $this->schema->associations[$assc_name];
                }

            } else {
                $assc_model = $e[0];
                $assc_name = $e[1];

                if($last_association !== null && $last_association->name !== $assc_name) {
                    $schema = Otter::getSchema($last_association->model);
                    $modelName = $last_association->name;
                    $association = $schema->associations[$assc_name];
                }
            }

            if($last_association === null) {
                $association = $this->schema->associations[$assc_name];
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
}
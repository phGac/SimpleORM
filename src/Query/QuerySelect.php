<?php

namespace Otter\ORM\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Otter;
use Otter\ORM\Maker\Query\QueryMaker;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryObject;

class QuerySelect extends Query {

    protected $onlyColumns = [];
    protected $associationsSchemas = null;

    public function __construct(Schema $schema) {
        parent::__construct($schema);
        $this->type = QueryType::SELECT;
        $this->query = new QueryObject();
        $this->setAssociationsSchemas();
    }

    private function setAssociationsSchemas() {
        foreach ($this->schema->associations as $key => $association) {
            if(strtolower($association->type) !== SchemaAssociation::BelongsToMany) {
                $schema = Otter::getSchema($association->model);
            } else {
                $schema = Otter::getSchema($association->through);
            }
            $this->associationsSchemas[$key] = $schema;
            unset($association);
        }
    }

    public function end() {
        $maker = new QueryMaker($this->type, $this->schema, $this->query, $this->values, $this->onlyColumns);
        return $maker->make();
    }

    public function select(array $onlyColumns = []) {
        $this->onlyColumns = $onlyColumns;
        return $this;
    }

    public function limit(int $limit): QuerySelect {
        $this->query->top = $limit;
        return $this;
    }

    public function where(array $whereArray): QuerySelect {
        $where = '';
        foreach ($whereArray as $key => $value) {            
            if(\gettype($key) !== 'integer') {
                $tableAndColumn = \explode('.', $key);
                $key = null;
                $model = null;
                if(\count($tableAndColumn) > 1) {
                    $key = $tableAndColumn[1];
                    $model = $tableAndColumn[0];
                } else {
                    $key = $tableAndColumn[0];
                    $model = $this->schema->name;
                }
                
                if(\gettype($value) === 'array') {
                    $simbol = \strtoupper($value[0]);
                    $value = $value[1];
                    $where .= "[$model].[$key] $simbol :$key AND ";
                } else if($value !== null) {
                    $where .= "[$model].[$key] = :$key AND ";
                }
            }
            else {
                switch(\strtoupper($value)) {
                    case 'OR': 
                        $where = \rtrim($where, 'AND ');
                        $where .= " OR ";
                    break;
                }
                continue;
            }

            if($model !== $this->schema->name) {
                if(array_key_exists($model, $this->associationsSchemas)) {
                    $columns = $this->associationsSchemas[$model]->columns[$key]->type;
                    $value = $this->valueToSQL($type, $value);
                }
            } else {
                $type = $this->schema->columns[$key]->type;
                $value = $this->valueToSQL($type, $value);
            }

            $this->values[":$key"] = $value;
        }
        $where = \rtrim($where, 'AND ');
        $this->query->where = $where;

        return $this;
    }

    public function include(array $includes): QuerySelect {
        $modelName = $this->schema->name;

        $joins = "";
        $columnsJoins = '';
        $last_association; $association;
        foreach ($includes as $include) {
            $join = \explode('.', $include);
            
            $assc_model = $join[0];
            $assc_name = null;

            if(count($join) === 1) {
                $assc_model = $modelName;
                $assc_name = $join[0];
                $association = $this->schema->associations[$assc_name];
            } else {
                $assc_name = $join[1];
                if($last_association->name !== $assc_name) {
                    $schema = Otter::getSchema($last_association->model);
                    $modelName = $last_association->name;
                    $association = $schema->associations[$assc_name];
                }
            }
            
            switch(\strtolower($association->type)) {
                case SchemaAssociation::HasOne:
                    $tableNameJoin = $this->associationsSchemas[$association->name]->table;
                    $foreignKey = $association->foreignKey;
                    $key = $association->key;

                    $columnsJoins .= $this->columnsJoin($this->associationsSchemas[ $association->name ]->columns, $assc_name);
                    if(! $association->strict) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case SchemaAssociation::HasMany:
                    $tableNameJoin = $this->associationsSchemas[$association->name]->table;
                    $foreignKey = $association->foreignKey;
                    $key = $association->key;

                    $columnsJoins .= $this->columnsJoin($this->associationsSchemas[ $association->name ]->columns, $assc_name);
                    if(! $association->strict) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case SchemaAssociation::BelongsTo:
                    $tableNameJoin = $this->associationsSchemas[$association->name]->table;
                    $foreignKey = $association->foreignKey;
                    $key = $association->key;

                    $columnsJoins .= $this->columnsJoin($this->associationsSchemas[ $association->name ]->columns, $assc_name);
                    if(! $association->strict) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case SchemaAssociation::BelongsToMany:
                    $through = Otter::getSchema($association->through); //$this->schema->associations[$association->name]->through
                    $throughBridge = $through->associations[$association->throughBridge];

                    $schemaJoin = (Otter::getSchema($throughBridge->model));
                    $tableNameJoin = $schemaJoin->table;
                    $throughTableName = $through->table;
                    $throughModelName = $through->name;
                    $throughForeignKey = $association->throughKey;
                    $foreignKey = $association->foreignKey;
                    $throughKey = $throughBridge->foreignKey;
                    $key = $throughBridge->key;

                    $columnsJoins .= $this->columnsJoin($schemaJoin->columns, $assc_name);
                    if(! $association->strict) {
                        $joins .= " LEFT JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    }
                break;
            }
            $last_association = $association;
        }
        $columnsJoins = \rtrim($columnsJoins, ',');
        $this->query->columnsJoins = $columnsJoins;
        $this->query->joins = $joins;

        return $this;
    }

    public function pagination(int $pag, int $maxPerPag): QuerySelect {
        if($pag <= 0) {
            throw new QueryException("Pagination CANNOT be less than 0. Start at 1.", 1);
        }

        $offset = ($maxPerPag + ($pag-1));
        $limit = ($maxPerPag * $pag);

        $this->query->pagination = "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        return $this;
    }

    private function columnsJoin(array $columnsJoin, string $associationName): ?string {
        $columns = '';
        if(count($this->onlyColumns) === 0) {
            foreach (array_keys($columnsJoin) as $key) {
                $columns .= "[$associationName].[$key] AS [$associationName.$key],";
            }
        }
        
        return $columns;
    }

}

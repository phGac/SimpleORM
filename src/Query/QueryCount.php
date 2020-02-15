<?php

namespace Otter\ORM\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Otter;
use Otter\ORM\Maker\Query\QueryMaker;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryObject;

class QueryCount extends QuerySelect {

    public function __construct(Schema $schema) {
        parent::__construct($schema);
        $this->type = QueryType::COUNT;
    }

    public function end() {
        $maker = new QueryMaker($this->type, $this->schema, $this->query, $this->values, $this->onlyColumns);
        $result = $maker->make();
        return ($result !== null) ? $result[0]->TOTAL : null;
    }

    /**
     * Unsupported method
     *
     * @param array $onlyColumns
     * @return void
     */
    public function find(array $onlyColumns = []) {
        throw new QueryException("Method not supported", 1);
    }

    /**
     * Unsupported method
     *
     * @param array $onlyColumns
     * @return void
     */
    public function findAll(array $onlyColumns = []) {
        throw new QueryException("Method not supported", 1);
    }

    /**
     * Unsupported method
     *
     * @param array $onlyColumns
     * @return void
     */
    public function limit(int $limit): QuerySelect {
        throw new QueryException("Method not supported", 1);
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
            }

            switch(\strtolower($association->type)) {
                case SchemaAssociation::HasOne:
                    $tableNameJoin = $this->associationsSchemas[$association->name]->table;
                    $foreignKey = $association->foreignKey;
                    $key = $association->key;

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

                    if(! $association->strict) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case SchemaAssociation::BelongsToMany:
                    $through = Otter::getSchema($this->schema->associations[$association->name]->through);
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
                        $joins .= " LEFT JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    }
                break;
            }
        }
        $this->query->joins = $joins;

        return $this;
    }

}

<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Maker\Query\QueryMaker;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryObject;

class QueryUpdate extends Query {

    public function __construct(Schema $schema) {
        parent::__construct($schema);
        $this->type = QueryType::UPDATE;
        $this->query = new QueryObject();
    }

    public function end() {
        $maker = new QueryMaker($this->type, $this->schema, $this->query, $this->values, []);
        return $maker->make(true);
    }

    public function update(array $columns) {
        $columnsString = "";
        $valuesString = "";
        foreach ($this->schema->columns as $key => $column) {

            if(array_key_exists($key, $columns)) {
                $columnsString .= "$key = :$key,";
                $this->values[":$key"] = $columns[$key];
            } else {
                // error
            }

        }
        $columnsString = \rtrim($columnsString, ',');

        $this->query->columns = $columnsString;

        return $this;
    }

    public function where(array $whereArray): QueryUpdate {
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
                // error
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

}
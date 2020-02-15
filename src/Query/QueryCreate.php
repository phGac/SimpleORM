<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\Maker\Query\QueryMaker;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryObject;

class QueryCreate extends Query {

    public function __construct(Schema $schema) {
        parent::__construct($schema);
        $this->type = QueryType::CREATE;
        $this->query = new QueryObject();
    }

    public function end() {
        $maker = new QueryMaker($this->type, $this->schema, $this->query, $this->values, []);
        return $maker->make(true);
    }

    public function create(array $columns) {
        $columnsString = "";
        $valuesString = "";
        foreach ($this->schema->columns as $key => $column) {

            if(! array_key_exists($key, $columns)) {
                if($column->required) {
                    if($column->defaultValue !== OtterValue::UNDEFINED) {
                        $columnsString .= "$key,";
                        if($column->otterDefaultValue !== OtterValue::UNDEFINED) {
                            $default = $column->otterDefaultValue;
                            $valuesString .= "$default,";
                        } else {
                            $valuesString .= ":$key,";
                            $this->values[":$key"] = $column->defaultValue; // usar valor por defecto
                        }
                        
                    } else {
                        // error
                    }
                } else {
                    $columnsString .= "$key,";
                    $valuesString .= ":$key,";
                    $this->values[":$key"] = $columns[$key];
                }
            } else { // exists
                $columnsString .= "$key,";
                $valuesString .= ":$key,";
                $this->values[":$key"] = $columns[$key];
            }

        }
        $columnsString = \rtrim($columnsString, ',');
        $valuesString = \rtrim($valuesString, ',');

        $this->query->columns = $columnsString;
        $this->query->values = $valuesString;
    }

}
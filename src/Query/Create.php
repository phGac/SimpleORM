<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\QueryRunner;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QueryCreate;

class Create {

    protected $schema;
    protected $query;
    protected $valuesToPrepare;

    public function __construct(Schema $schema, array $columns) {
        $this->schema = $schema;
        $this->query = new QueryCreate($schema->table);
        $this->valuesToPrepare = [];
        $this->setColumns($columns);
    }

    public function end(bool $onlyReturnResult = true) {
        $sql = \Otter\ORM\Maker\Query\QueryMakerCreate::make($this->query);
        $result = QueryRunner::execute($sql, $this->valuesToPrepare, false, false, false);
        if(! $onlyReturnResult) {
            return $result;
        } else {
            return ($result->affectedRows === 1) ? true : false;
        }
    }

    private function setColumns(array $columns): void {
        $columnsToSet = [];
        $valuesToInsert = [];
        $valuesToPrepare = [];
        foreach ($this->schema->columns as $key => $column) {
            if(! array_key_exists($key, $columns)) {
                if($column->required) {
                    if($column->defaultValue !== OtterValue::UNDEFINED) {
                        $columnsToSet[] = $key;
                        if($column->otterDefaultValue !== OtterValue::UNDEFINED) {
                            $default = $column->otterDefaultValue;
                            $valuesToInsert[] = $default;
                        } else {
                            $valuesToInsert[] = ":$key";
                            $valuesToPrepare[":$key"] = $column->defaultValue;
                        }
                        
                    } else {
                        // error
                    }
                } else {
                    $columnsToSet[] = $key;
                    $valuesToInsert[] = ":$key";
                    $valuesToPrepare[":$key"] = $column->defaultValue;
                }
            } else { // exists
                $columnsToSet[] = $key;
                $valuesToInsert[] = ":$key";
                $valuesToPrepare[":$key"] = $columns[$key];
            }
        }

        $this->query->columnsToSet = $columnsToSet;
        $this->query->valuesToInsert = $valuesToInsert;
        $this->valuesToPrepare = $valuesToPrepare;
    }

}
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

}
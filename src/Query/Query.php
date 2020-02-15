<?php

namespace Otter\ORM\Query;

use Otter\ORM\Schema\Schema;

abstract class Query {

    protected $query;
    protected $type;
    protected $values;
    protected $schema;

    public function __construct(Schema $schema) {
        $this->schema = $schema;
        $this->query = [];
        $this->type = null;
        $this->values = [];
    }

    public function end() {}

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

abstract class QueryType {
    public const SELECT = "SELECT";
    public const CREATE = "CREATE";
    public const UPDATE = "UPDATE";
    public const DELETE = "DELETE";

    public const COUNT = "SELECT-COUNT";
}

class QueryObject {

    // SELECT
    public $top = null;
    public $count = false;
    public $columnsJoins = null;
    public $joins = null;
    public $where = null;
    public $groupby = null;
    public $having = null;
    public $orderby = null;
    public $pagination = null;

    // CREATE - UPDATE - DELETE
    public $columns;
    public $values;
    public $withoutWhere;
}
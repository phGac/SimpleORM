<?php

namespace Otter\ORM\Query {
    abstract class QueryType {
        public const SELECT = "SELECT";
        public const CREATE = "CREATE";
        public const UPDATE = "UPDATE";
        public const DELETE = "DELETE";
    
        public const COUNT = "SELECT-COUNT";
    }
    class QuerySelect {
        public $select = [];
        public $onlySelect = null;
        public $top = null;
        public $from = null;
        public $join = [];
        public $where = [];
        public $groupby = [];
        public $having = [];
        public $orderby = [];
        public $pagination = null;

        public function __construct(string $fromTable, string $fromAlias, array $onlySelect = null) {
            $this->from = "[$fromTable] AS [$fromAlias]";
            $this->onlySelect = $onlySelect;
        }
    }
    class QueryCreate {
        public $into = null;
        public $columnsToSet = [];
        public $valuesToInsert = [];

        public function __construct(string $tableName) {
            $this->into = $tableName;
        }
    }
    class QueryUpdate {
        public $update = null;
        public $columnsToSet = [];
        public $where = [];

        public function __construct(string $tableName) {
            $this->update = $tableName;
        }
    }
    class QueryDelete {
        public $delete = null;
        public $where = [];

        public function __construct(string $tableName) {
            $this->delete = $tableName;
        }
    }
}

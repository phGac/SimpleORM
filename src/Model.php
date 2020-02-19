<?php

namespace Otter\ORM;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\Select;
use Otter\ORM\Query\Count;
use Otter\ORM\Query\Create;
use Otter\ORM\Query\Update;
use Otter\ORM\Query\Delete;

class Model {
    protected $schema;

    public function __construct(Schema $schema) {
        $this->schema = $schema;
    }

    public function find(array $onlyColumns = []): Select {
        $select = new Select($this->schema, $onlyColumns);
        $select->top(1);
        return $select;
    }

    public function findAll(array $onlyColumns = []): Select {
        return new Select($this->schema, $onlyColumns);
    }

    public function count(): Count {
        return new Count($this->schema);
    }

    public function create(array $columns, bool $onlyReturnResult = true) {
        $create = new Create($this->schema, $columns);
        return $create->end($onlyReturnResult);
    }

    public function update(array $columns, array $where, bool $onlyReturnResult = true) {
        $update = new Update($this->schema, $columns);
        $update->where($where);
        return $update->end($onlyReturnResult);
    }

    public function updateAll(array $columns, bool $onlyReturnResult = true) {
        $update = new Update($this->schema, $columns);
        return $update->end($onlyReturnResult);
    }

    public function delete(array $where, bool $onlyReturnResult = true) {
        $delete = new Delete($this->schema);
        $delete->where($where);
        return $delete->end($onlyReturnResult);
    }

    public function deleteAll(bool $onlyReturnResult = true) {
        $delete = new Delete($this->schema);
        return $delete->end($onlyReturnResult);
    }
}

class PlainModel {}

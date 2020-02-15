<?php

namespace Otter\ORM;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QuerySelect;
use Otter\ORM\Query\QueryCreate;
use Otter\ORM\Query\QueryUpdate;
use Otter\ORM\Query\QueryDelete;
use Otter\ORM\Query\QueryCount;

class Model {
    protected $schema;

    public function __construct(Schema $schema) {
        $this->schema = $schema;
    }

    public function find(array $onlyColumns = []): QuerySelect {
        $querySelect = new QuerySelect($this->schema);
        $querySelect->select($onlyColumns)
                    ->limit(1);
        return $querySelect;
    }

    public function findAll(array $onlyColumns = []): QuerySelect {
        $querySelect = new QuerySelect($this->schema);
        $querySelect->select($onlyColumns);
        return $querySelect;
    }

    public function count(): QueryCount {
        return new QueryCount($this->schema);
    }

    public function create(array $columns): bool {
        $queryCreate = new QueryCreate($this->schema);
        return $queryCreate->create($columns);
    }

    public function update(array $columns, array $where): bool {
        $queryUpdate = new QueryUpdate($this->schema);
        return $queryUpdate->update($columns)->where($where)->end();
    }

    public function updateAll(array $columns): bool {
        $queryUpdate = new QueryUpdate($this->schema);
        return $queryUpdate->update($columns)->end();
    }

    public function delete(array $where): bool {
        $queryDelete = new QueryDelete($this->schema);
        return $queryDelete->where($where)->end();
    }

    public function deleteAll(): bool {
        $queryDelete = new QueryDelete($this->schema);
        return $queryDelete->end();
    }

    /*
    public function build(array $data = []) {
        $object = new SimpleModel($this->schema);
        foreach ($this->schema->columns as $key => $value) {
            $object->$key = (isset($data[$key])) ? $data[$key] : null;
        }
        return $object;
    }

    public function make($object) {
        $vars = \get_object_vars($object);
        $queryCreate = new QueryCreate();
    }
    */
}

class PlainModel {}

class SimpleModel {
    private $schema;

    public function __construct($schema) {
        $this->schema = $schema;
    }

    public function __set(string $name, $value) {
        if(array_key_exists($name, $this->schema->columns)) {
            $this->$name = $value;
        }
    }
}

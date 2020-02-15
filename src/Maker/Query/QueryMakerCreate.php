<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryObject;

abstract class QueryMakerCreate {

    public static function make(Schema $schema, QueryObject $queryObject) {
        $table = $schema->table;
        $columns = $queryObject->columns;
        $values = $queryObject->values;

        $query = "INSERT INTO [$table] ($columns) VALUES ($values);";

        return $query;
    }

}

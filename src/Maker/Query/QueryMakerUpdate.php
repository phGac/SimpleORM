<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryObject;

abstract class QueryMakerUpdate {

    public static function make(Schema $schema, QueryObject $queryObject) {
        $table = $schema->table;
        $columns = $queryObject->columns;
        $where = $queryObject->where;

        if($where !== null) {
            $query = "UPDATE [$table] SET $columns WHERE $where;";
        } else {
            $query = "UPDATE [$table] SET $columns;";
        }

        return $query;
    }

}

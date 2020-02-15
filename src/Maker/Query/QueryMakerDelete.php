<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryObject;

abstract class QueryMakerDelete {

    public static function make(Schema $schema, QueryObject $queryObject) {
        $table = $schema->table;
        $where = $queryObject->where;

        if($where !== null) {
            $query = "DELETE [$table] WHERE $where;";
        } else {
            $query = "DELETE [$table];";
        }

        return $query;
    }

}

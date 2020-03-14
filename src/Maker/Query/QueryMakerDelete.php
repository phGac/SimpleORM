<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryDelete;

abstract class QueryMakerDelete {

    public static function make(QueryDelete $query) {
        $table = $query->delete;
        $where = $query->where;

        if(count($query->where) > 0) {
            $query = "DELETE [$table] WHERE $where;";
        } else {
            $query = "DELETE [$table];";
        }

        return $query;
    }

}

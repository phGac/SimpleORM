<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryUpdate;

abstract class QueryMakerUpdate {

    public static function make(QueryUpdate $query) {
        $table = $query->update;
        $columnsToSet = implode(', ',$query->columnsToSet);
        $where = implode(' ', $query->where);

        if(count($query->where) > 0) {
            $query = "UPDATE [$table] SET $columnsToSet WHERE $where;";
        } else {
            $query = "UPDATE [$table] SET $columns;";
        }

        return $query;
    }

}

<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Query\QueryCreate;

abstract class QueryMakerCreate {

    public static function make(QueryCreate $query) {
        $table = $query->into;
        $columns = \implode(', ', $query->columnsToSet);
        $values = \implode(', ', $query->valuesToInsert);

        $query = "INSERT INTO [$table] ($columns) VALUES ($values);";

        return $query;
    }

}

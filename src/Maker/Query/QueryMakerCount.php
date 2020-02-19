<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QuerySelect;

abstract class QueryMakerCount extends QueryMakerSelect {

    public static function make(Schema $schema, QuerySelect $query) {
        $sql = 'SELECT COUNT(*) AS [TOTAL]';
        $sql .= ' FROM '.$query->from;
        if(count($query->join) > 0)
            $sql .= ' '.implode(' ', $query->join);
        if(count($query->where) > 0)
            $sql .= ' WHERE '.implode(' ', $query->where);
        if(count($query->groupby) > 0)
            $sql .= ' GROUP BY '.implode(', ', $query->groupby);
        if(count($query->having) > 0)
            $sql .= ' HAVING '.implode(', ', $query->groupby);

        return $sql;
    }

}

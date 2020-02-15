<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryObject;

abstract class QueryMakerCount extends QueryMakerSelect {

    public static function make(Schema $schema, array $onlyColumns, QueryObject $query) {
        $joins = $query->joins;
        $where = $query->where;
        $having = $query->having;
        $groupby = $query->groupby;
        $orderby = $query->orderby;
        
        $query = 'SELECT COUNT(*) AS [TOTAL] ';
        $query .= self::from($schema);
        $query .= self::join($joins);
        $query .= self::where($where);
        $query .= self::having($having);
        $query .= self::orderby($orderby);

        return $query;
    }

}

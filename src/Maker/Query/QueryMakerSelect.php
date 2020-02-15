<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QueryObject;

abstract class QueryMakerSelect {

    public static function make(Schema $schema, array $onlyColumns, QueryObject $query) {
        $top = $query->top;
        $joins = $query->joins;
        $where = $query->where;
        $having = $query->having;
        $groupby = $query->groupby;
        $orderby = $query->orderby;
        $pagination = $query->pagination;
        $columnsJoins = $query->columnsJoins;
        
        $query = 'SELECT';
        $query .= self::top($top);
        $query .= self::columns($schema, $onlyColumns);
        $query .= self::columnsJoins($onlyColumns, $columnsJoins);
        $query .= self::from($schema);
        $query .= self::join($joins);
        $query .= self::where($where);
        $query .= self::having($having);
        $query .= self::orderby($orderby);
        $query .= self::pagination($pagination, $orderby, $schema->columns, $schema->name);

        return $query;
    }

    private static function top($top): string {
        return ($top !== null) ? " TOP $top" : ' ';
    }

    private static function columns(Schema $schema, array $onlyColumns) {
        $modelName = $schema->name;
        $tableName = $schema->table;
        $tableColumns = \array_keys($schema->columns);
        $columns = (count($onlyColumns) <= 0) ? $tableColumns : $onlyColumns;

        $stringcolumns = '';
        foreach ($columns as $key) {
            $array = explode('.', $key);
            if(count($array) > 1) {
                $model_name = $array[0];
                $column_name = $array[1];
                $stringcolumns .= " [$model_name].[$column_name] AS [$model_name.$column_name],";
            } else if(array_search($key, $tableColumns) !== null) {
                $stringcolumns .= " [$modelName].[$key],";
            }
        }

        $stringcolumns = \rtrim($stringcolumns, ',');
        return $stringcolumns;
    }

    private static function columnsJoins($onlyColumns, $columnsJoins): string {
        if(count($onlyColumns) === 0 && $columnsJoins !== null) {
            return ", $columnsJoins";
        }
        return "";
    }

    protected static function from(Schema $schema) {
        $modelName = $schema->name;
        $tableName = $schema->table;

        return " FROM [$tableName] AS [$modelName]";
    }

    protected static function join($joins): string {
        return ($joins !== null) ? " $joins" : "";        
    }

    protected static function where($where): string {
        return ($where !== null) ? " WHERE $where" : '';
    }

    protected static function groupby($groupby): string {
        return ($groupby !== null) ? " GROUP BY $groupby" : '';
    }

    protected static function having($having): string {
        return ($having !== null) ? " HAVING $having" : '';
    }

    protected static function orderby($orderby): string {
        return ($orderby !== null) ? " ORDER BY $orderby" : '';
    }

    private static function pagination($pagination, $orderby, $columns, $modelName): string {
        if($pagination !== null) {
            $query = "";
            if($orderby === null) {
                foreach ($columns as $key => $value) {
                    if(isset($value['primaryKey']) && $value['primaryKey']) {
                        $query .= " ORDER BY [$modelName].[$key]";
                        break;
                    }
                }
            }
            $query .= " $pagination";
            return $query;
        }
        return "";
    }

}

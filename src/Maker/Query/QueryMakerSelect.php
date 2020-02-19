<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Schema\Schema;
use Otter\ORM\Query\QuerySelect;

abstract class QueryMakerSelect {

    public static function make(Schema $schema, QuerySelect $query) {
        $sql = 'SELECT';
        if($query->top !== null)
            $sql .= ' TOP '.$query->top;
        $sql .= self::columns($schema, $query->onlySelect);
        if(count($query->join) > 0 && count($query->onlySelect) === 0)
            $sql .= ', '.implode(', ', $query->select);
        $sql .= ' FROM '.$query->from;
        if(count($query->join) > 0)
            $sql .= ' '.implode(' ', $query->join);
        if(count($query->where) > 0)
            $sql .= ' WHERE '.implode(' ', $query->where);
        if(count($query->groupby) > 0)
            $sql .= ' GROUP BY '.implode(', ', $query->groupby);
        if(count($query->having) > 0)
            $sql .= ' HAVING '.implode(', ', $query->groupby);
        if(count($query->orderby) > 0)
            $sql .= ' ORDER BY '.implode(', ', $query->orderby);
        if($query->pagination !== null) 
            $sql .= ' '.self::pagination($query->pagination, $query->orderby, $schema);

        return $sql;
    }

    private static function columns(Schema $schema, array $onlySelect) {
        $modelName = $schema->name;
        $tableName = $schema->table;
        $tableColumns = \array_keys($schema->columns);
        $columns = (count($onlySelect) === 0) ? $tableColumns : $onlySelect;

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

    private static function pagination($pagination, $orderby, $schema): string {
        if(count($orderby) !== 0) {
            return ' '.$pagination;
        } else {
            $query = "";
            if($schema->pk !== '' && $schema->pk !== null) {
                $query = ' ORDER BY ['.$schema->name.'].['.$schema->pk.']';
            } else {
                $key = (array_keys($schema->columns)[0]);
                $query = ' ORDER BY ['.$schema->name."].[$key]";
            }
            return $query.' '.$pagination;
        }
    }

}

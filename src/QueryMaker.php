<?php

namespace Otter\ORM;

class QueryMaker {

    private $schema;
    private $queryArray;
    private $arrayValuesPrepare;
    private $queryType;
    private $onlyColumns;

    public function __construct(string $schema) {
        $this->schema = $schema;
        $this->queryFinal = '';
        $this->queryArray = [
            'top' => null,
            'count' => false,
            'columns-joins' => null,
            'join' => null,
            'where' => null,
            'groupby' => null,
            'having' => null,
            'orderby' => null,
            'pagination' => null,
        ];
        $this->arrayValuesPrepare = [];
        $this->queryType = null;
        $this->onlyColumns = [];
    }

    public function selectAll(array $onlyColumns = []): QueryMaker {
        $this->queryType = QueryType::SELECT;
        $this->onlyColumns = $onlyColumns;
        return $this;
    }

    public function select(array $onlyColumns = []): QueryMaker {
        $this->queryType = QueryType::SELECT;
        $this->onlyColumns = $onlyColumns;
        $this->queryArray['top'] = 1;
        return $this;
    }

    public function count() {
        $this->queryType = QueryType::COUNT;
        $this->queryArray['count'] = true;
        return $this;
    }

    public function limit(int $limit): QueryMaker {
        $this->queryArray['top'] = $limit;
        return $this;
    }

    public function where(array $whereArray): QueryMaker {
        $where = '';
        foreach ($whereArray as $key => $value) {            
            if(\gettype($key) !== 'integer') {
                $tableAndColumn = \explode('.', $key);
                $key = $tableAndColumn[1];
                $model = $tableAndColumn[0];
                if(\gettype($value) === 'array') {
                    $simbol = \strtoupper($value[0]);
                    $value = $value[1];
                    $where .= "[$model].[$key] $simbol :$key AND ";
                } else if($value !== null) {
                    $where .= "[$model].[$key] = :$key AND ";
                }
            }
            else {
                switch(\strtoupper($value)) {
                    case 'OR': 
                        $where = \rtrim($where, 'AND ');
                        $where .= " OR ";
                    break;
                }
                continue;
            }

            if(! array_key_exists($key, $this->schema::$columns)) {
                if(array_key_exists($model, $this->schema::$associations)) {
                    $type = $this->schema::$associations[$model]['schema']::$columns[$key]['type'];
                    $value = $this->valueToSQL($type, $value);
                }
            } else {
                $type = $this->schema::$columns[$key]['type'];
                $value = $this->valueToSQL($type, $value);
            }

            $this->arrayValuesPrepare[":$key"] = $value;
        }
        $where = \rtrim($where, 'AND ');
        $this->queryArray['where'] = $where;

        return $this;
    }

    public function join(array $joinsArray): QueryMaker {
        $modelName = $this->schema::$modelName;
        $associations = $this->schema::$associations;

        $joins = "";
        $columnsJoins = '';
        foreach ($joinsArray as $join) {
            $join = \explode('.', $join);
            $assc_model = $join[0];
            $assc_name = $join[1];

            $last_association;
            $association;
            if($assc_model === $modelName) {
                $association = $associations[$assc_name];
            } else if (! array_key_exists($assc_name, $this->schema::$associations)) {
                $modelName = $assc_model; // $last_association['schema']::$modelName;
                $association = $last_association['schema']::$associations[$assc_name];
            }
            switch($association['type']) {
                case ModelAssociation::HasOne:
                    $tableNameJoin = $association['schema']::$tableName;
                    $foreignKey = $association['foreignKey'];
                    $key = $association['key'];

                    $columnsJoins .= $this->columnsJoin($association['schema']::$columns, $assc_name);
                    if(! isset($association['strict']) || $association['strict'] === false) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case ModelAssociation::HasMany:
                    $tableNameJoin = $association['schema']::$tableName;
                    $foreignKey = $association['foreignKey'];
                    $key = $association['key'];

                    $columnsJoins .= $this->columnsJoin($association['schema']::$columns, $assc_name);
                    if(! isset($association['strict']) || $association['strict'] === false) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case ModelAssociation::BelongsTo:
                    $tableNameJoin = $association['schema']::$tableName;
                    $foreignKey = $association['foreignKey'];
                    $key = $association['key'];

                    $columnsJoins .= $this->columnsJoin($association['schema']::$columns, $assc_name);
                    if(! isset($association['strict']) || $association['strict'] === false) {
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$modelName].[$foreignKey] = [$assc_name].[$key]";
                    }
                break;
                case ModelAssociation::BelongsToMany:
                    $tableNameJoin = $association['schema']::$tableName;
                    $throughTableName = $association['through']::$tableName;
                    $throughModelName = $association['through']::$modelName;
                    $throughForeignKey = $association['throughForeignKey'];
                    $foreignKey = $association['foreignKey'];
                    $throughKey = $association['throughKey'];
                    $key = $association['key'];

                    if(! isset($association['strict']) || $association['strict'] === false) {
                        $joins .= " LEFT JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " LEFT JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    } else {
                        $joins .= " INNER JOIN [$throughTableName] AS [$throughModelName] ON [$modelName].[$foreignKey] = [$throughModelName].[$throughForeignKey]";
                        $joins .= " INNER JOIN [$tableNameJoin] AS [$assc_name] ON [$throughModelName].[$throughKey] = [$assc_name].[$key]";
                    }

                    $columnsJoins .= $this->columnsJoin($association['schema']::$columns, $assc_name);
                    break;
            }
            $last_association = $association;
        }
        
        $columnsJoins = \rtrim($columnsJoins, ',');
        $this->queryArray['columns-joins'] = $columnsJoins;
        $this->queryArray['join'] = $joins;

        return $this;
    }

    private function columnsJoin(array $columnsJoin, string $associationName): ?string {
        if($this->queryArray['count']) {
            return null;
        }

        $columns = '';
        if(count($this->onlyColumns) === 0) {
            foreach (array_keys($columnsJoin) as $key) {
                $columns .= "[$associationName].[$key] AS [$associationName.$key],";
            }
        } else {
            /*
            $keys = array_keys($columnsJoin);
            foreach ($this->onlyColumns as $key => $value) {
                $value = explode('.', $value);
                if(count($value) > 1) {
                    $value_name = $value[0];
                    $value_key = $value[1];
                    if($associationName === $value_name && array_key_exists($value_key, $columnsJoin)) {
                        $columns .= "[$associationName].[$value_key] AS [$associationName.$value_key],";
                    }
                }
                
            }
            */
        }
        
        return $columns;
    }

    public function orderBy(array $orderByArray): QueryMaker {
        $orderBy = '';
        foreach ($orderByArray as $key => $value) {
            if(\gettype($key) !== 'integer') {
                $e = \explode('.', $key);
                $model = $e[0];
                $column = $e[1];
                $orderBy .= "[$model].[$column] $value,";
            } else {
                $e = \explode('.', $value);
                $model = $e[0];
                $column = $e[1];

                $orderBy .= "[$model].[$column] ASC,";
            }
        }
        $orderBy = \rtrim($orderBy, ',');
        $this->queryArray['orderby'] = $orderBy;

        return $this;
    }

    public function pagination(int $pag, int $maxPerPage): QueryMaker {
        $offset = $maxPerPage * ($pag-1);
        $limit = ($maxPerPage * $pag);
        $pagination = "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        $this->queryArray['pagination'] = $pagination;
        return $this;
    }

    public function groupBy(array $brouprByArray): QueryMaker {
        $brouprBy = '';
        foreach ($brouprByArray as $key => $value) {
            $e = \explode('.', $value);
            $model = $e[0];
            $column = $e[1];
            $brouprBy .= "[$model].[$column],";
        }
        $brouprBy = \rtrim($brouprBy, ',');
        $this->queryArray['groupby'] = $brouprBy;

        return $this;
    }

    public function end() 
    {
        $query = $this->makeQuery();
        SimpleORM::$lastQuery = $query;
        $this->arrayValuesPrepare = array_map(function($val) {
            if(gettype($val) === 'boolean') {
                return (int) $val;
            }
            return $val;
        }, $this->arrayValuesPrepare);

        if($this->queryArray['count']) {
            return QueryRow::count($query, $this->arrayValuesPrepare);
        }
        if($this->queryArray['join'] !== null)
            return QueryRow::executeWithJoins($this->schema, $query, $this->arrayValuesPrepare);
        else
            return QueryRow::execute($query, $this->arrayValuesPrepare);
    }

    private function makeQuery(): string {
        $tableName = $this->schema::$tableName;
        $modelName = $this->schema::$modelName;

        $columns = $this->columns();

        $query = '';
        switch($this->queryType) {
            case QueryType::SELECT:
                $query = 'SELECT';
                $top = ($this->queryArray['top'] !== null) ? 'TOP '.$this->queryArray['top'] : '';
                if($this->queryArray['top'] !== null) {
                    $query .= ' TOP '.$this->queryArray['top'];
                }

                $query .= " $columns";
                if($this->onlyColumns === null && $this->queryArray['columns-joins'] !== null) {
                    $query .= ", ".$this->queryArray['columns-joins'];
                }
                $query .= " FROM [$tableName] AS [$modelName]";
                if($this->queryArray['join'] !== null)
                    $query .= ' '.$this->queryArray['join'];
                if($this->queryArray['where'] !== null)
                    $query .= ' WHERE '.$this->queryArray['where'];
                if($this->queryArray['groupby'] !== null)
                    $query .= ' GROUP BY '.$this->queryArray['groupby'];
                if($this->queryArray['having'] !== null)
                    $query .= ' HAVING '.$this->queryArray['having'];
                if($this->queryArray['orderby'] !== null)
                    $query .= " ORDER BY ".$this->queryArray['orderby'];
                if($this->queryArray['pagination'] !== null) {
                    if($this->queryArray['orderby'] === null) {
                        foreach ($this->schema::$columns as $key => $value) {
                            if(isset($value['primaryKey']) && $value['primaryKey']) {
                                $query .= " ORDER BY [$modelName].[$key]";
                                break;
                            }
                        }

                        $query .= " ".$this->queryArray['pagination'];
                    } else {
                        $query .= " ".$this->queryArray['pagination'];
                    }
                }
            break;
            case QueryType::UPDATE:
            break;
            case QueryType::CREATE:
            break;
            case QueryType::DELETE:
            break;
            case QueryType::COUNT:
                $query = "SELECT COUNT(*) AS [COUNT] FROM [$tableName] AS [$modelName]";
                if($this->queryArray['join'] !== null)
                    $query .= ' '.$this->queryArray['join'];
                if($this->queryArray['where'] !== null)
                    $query .= ' WHERE '.$this->queryArray['where'];
                if($this->queryArray['groupby'] !== null)
                    $query .= ' GROUP BY '.$this->queryArray['groupby'];
                if($this->queryArray['having'] !== null)
                    $query .= ' HAVING '.$this->queryArray['having'];
            break;
        }

        return $query.';';
    }

    private function columns(): string {
        if($this->queryArray['count']) {
            return "";
        }

        $modelName = $this->schema::$modelName;
        $tableColumns = array_keys($this->schema::$columns);
        $columns = (count($this->onlyColumns) <= 0) ? $tableColumns : $this->onlyColumns;

        $stringcolumns = '';
        foreach ($columns as $key) {
            $array = explode('.', $key);
            if(count($array) > 1) {
                $model_name = $array[0];
                $column_name = $array[1];
                $stringcolumns .= "[$model_name].[$column_name] AS [$model_name.$column_name],";
            } else if(array_search($key, $tableColumns) !== null) {
                $stringcolumns .= "[$modelName].[$key],";
            }
        }

        $stringcolumns = \rtrim($stringcolumns, ',');
        return $stringcolumns;
    }

    private static function valueToSQL(string $columnType, $originalValue) {
        $value = $originalValue;
        switch($columnType) {
            case "boolean":
            $value = ($originalValue === true) ? 1 : 0;
            break;
            case null:
            $value = "NULL";
            break;
        }
        return $value;
    }

}

abstract class QueryType {
    public const SELECT = "SELECT";
    public const CREATE = "CREATE";
    public const UPDATE = "UPDATE";
    public const DELETE = "DELETE";

    public const COUNT = "SELECT-COUNT";
}
<?php

namespace Otter\ORM\Maker\Query;

use Otter\ORM\Otter;
use Otter\ORM\QueryRunner;
use Otter\ORM\Query\QueryType;
use Otter\ORM\Query\QueryObject;
use Otter\ORM\Schema\Schema;

class QueryMaker {

    protected $schema;
    protected $query;
    protected $arrayValuesPrepare;
    protected $queryType;
    protected $onlyColumns;

    public function __construct(string $queryType, Schema $schema, QueryObject $query, array $arrayValuesPrepare, array $onlyColumns = []) {
        $this->schema = $schema;
        $this->queryType = $queryType;
        $this->query = $query;
        $this->arrayValuesPrepare = $arrayValuesPrepare;
        $this->onlyColumns = $onlyColumns;
    }

    public function make(bool $returnBoolean = false) {
        $query = "";
        switch($this->queryType) {
            case QueryType::SELECT:
                $query = QueryMakerSelect::make($this->schema, $this->onlyColumns, $this->query);
            break;
            case QueryType::UPDATE:
                $query = QueryMakerUpdate::make($this->schema, $this->query);
            break;
            case QueryType::CREATE:
                $query = QueryMakerCreate::make($this->schema, $this->query);
            break;
            case QueryType::DELETE:
                $query = QueryMakerDelete::make($this->schema, $this->query);
            break;
            case QueryType::COUNT:
                $query = QueryMakerCount::make($this->schema, [], $this->query);
            break;
        }

        $this->arrayValuesPrepare = array_map(function($val) {
            if(gettype($val) === 'boolean') {
                return (int) $val;
            }
            return $val;
        }, $this->arrayValuesPrepare);

        Otter::$lastQuery = $query;

        if($this->query->count) {
            return QueryRunner::count($query, $this->arrayValuesPrepare);
        }
        if($this->query->joins !== null)
            return QueryRunner::executeWithJoins($this->schema, $query, $this->arrayValuesPrepare);
        else
            return QueryRunner::execute($query, $this->arrayValuesPrepare, $returnBoolean);
    }

}

<?php

namespace Otter\ORM\Query;

use Otter\ORM\Otter;
use Otter\ORM\OtterValue;
use Otter\ORM\QueryRunner;
use Otter\ORM\Exception\QueryException;
use Otter\ORM\Schema\Schema;
use Otter\ORM\Schema\SchemaAssociation;
use Otter\ORM\Query\QuerySelect;

class Count extends Select {

    public function __construct(Schema $schema, array $onlyColumns = []) {
        parent::__construct($schema, $onlyColumns);
    }

    public function end(bool $onlyReturnData = true): ?int {
        $sql = \Otter\ORM\Maker\Query\QueryMakerCount::make($this->schema, $this->query);
        $result = QueryRunner::execute($sql, $this->valuesToPrepare, true, false);
        return ($result !== null) ? $result->data->TOTAL : null;
    }

    /**
     * Unsupported method
     *
     * @param int $top
     * @return Exception
     */
    public function top(int $top): Select {
        throw new QueryException("Unsupported Method", 1);
    }

    /**
     * Undocumented function
     *
     * @param array $joins
     * @return Select
     */
    public function join(array $joins): Select {
        $this->joinAux($joins, false);
        return $this;
    }

    /**
     * Method not supported
     *
     * @param array $orderByArray
     * @return QuerySelect
     */
    public function orderBy(array $orderBy): Select {
        throw new QueryException("Unsupported Method", 1);
    }

    /**
     * Method not supported
     *
     * @param integer $pag
     * @param integer $maxPerPag
     * @return QuerySelect
     */
    public function pagination(int $pag, int $maxPerPag): Select {
        throw new QueryException("Unsupported Method", 1);
    }

    public function groupBy(array $groupBy): Select {
        $this->query->groupby = $groupBy;
        return $this;
    }

}
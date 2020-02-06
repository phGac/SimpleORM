<?php

namespace Otter\ORM;

class Model {

    protected $schema;

    public function __construct($schema) {
        $this->schema = $schema;
        $this->lastQuery = '';
        $this->query = [];
    }

    public function __toString() {
        $tableName = self::$tableName;
        $columns = '';
        foreach ($this->$columns as $key => $value) {
            $columns .= "\n\t\t$key ";
            $features = '{';
            foreach ($value as $k => $v) {
                if($k === 'references') {
                    $model = $v['model'];
                    $foreignKey = $v['foreignKey'];
                    $features .= "\n\t\t\t$k = $model\[$foreignKey\]";
                } else {
                    $features .= "\n\t\t\t$k = $v";
                }
            }
            $features .= "\n\t\t},";
            $columns .= $features;
        }

        return "Model Object [$tableName] {\n\tcolumns => [$columns\n\t]\n}";
    }

    public function find(array $onlyColumns = []): QueryMaker {
        return (new QueryMaker($this->schema))->select($onlyColumns);
    }

    public function findAll(array $onlyColumns = []): QueryMaker {
        return (new QueryMaker($this->schema))->selectAll($onlyColumns);
    }

    public function count(): QueryMaker {
        return (new QueryMaker($this->schema))->count();
    }

    public function create(array $columns): bool {
        return (new QueryMaker($this->schema))->create($columns);
    }

    public function update(array $columns): bool {
        return (new QueryMaker($this->schema))->update($columns);
    }

    public function delete(array $where = []): bool {
        return (new QueryMaker($this->schema))->delete($where);
    }
}

class PlainModel {}

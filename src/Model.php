<?php

namespace Otter\ORM;

class Model {

    protected $schema;
    protected $lastQuery;

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

    public function toPlainObject() {
        $attributes = get_object_vars($this);
        $plainObject = new PlainModel();
        foreach ($attributes as $key => $value) {
            $plainObject->$key = $value;
        }
        return $plainObject;
    }

    public function find() {
        return (new QueryMaker($this->schema))->select();
    }

    public function findAll() {
        return (new QueryMaker($this->schema))->selectAll();
    }

    /*
    public function selectAll(array $options = [], bool $plainObjects = true) {
        $sql = QueryMaker::selectAll($this->schema, $options);
        $this->lastQuery = $sql;
        return \Otter\ORM\QueryRow::db($sql, [], $plainObjects);
    }

    public function select(array $options = [], bool $plainObject = true) {
        $sql = QueryMaker::select($this->schema, $options);
        $this->lastQuery = $sql;
        return \Otter\ORM\QueryRow::db($sql, [], $plainObject);
    }

    public function create(array $columns) {
        $sql = QueryMaker::insert($this->tableName, $this->columns, $columns);
        $this->lastQuery = $sql;
        return \Otter\ORM\QueryRow::query($sql, []);
    }

    public function update(array $columns, array $options = []) {
        $sql = QueryMaker::update($this->schema, $columns, $options);
        $this->lastQuery = $sql;
        return \Otter\ORM\QueryRow::query($sql, []);
    }

    public function delete(array $options = []) {
        $sql = QueryMaker::delete($this->schema, $options);
        $this->lastQuery = $sql;
        return \Otter\ORM\QueryRow::query($sql, []);
    }

    public function count(array $options = []): int {
        $sql = QueryMaker::count($this->schema, $options);
        $this->lastQuery = $sql;
        $count = \Otter\ORM\QueryRow::db($sql, []);
        return (int) $count[0]->total;
    }
    */
}

class PlainModel {

    public function __toString() {
        $attributes = get_object_vars($this);
        $text = "\nPlain Object {";
        foreach ($attributes as $key => $value) {
            $text .= "\n\t$key:\t$value";
        }
        $text .= "\n}";
        return $text;
    }

}

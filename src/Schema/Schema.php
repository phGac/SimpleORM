<?php

namespace Otter\ORM\Schema;

use stdClass;
use SimpleXMLElement;
use Otter\ORM\OtterValue;
use Otter\ORM\Schema\Association;
use Otter\ORM\Schema\Column;

class Schema {

    public $pk;
    public $name;
    public $table;
    public $columns;
    public $associations;

    public function __construct(string $name, SimpleXMLElement $schemaXML) {
        $json = \json_encode($schemaXML);
        $schema = \json_decode($json);
        
        $this->name = $name;
        $this->table = $schema->{'@attributes'}->table;
        $this->setColumns($schema);
        $this->setAssociations($schema);
    }

    private function setColumns(stdClass $schema): void {
        $columns = [];
        foreach ($schema->columns->column as $key => $column) {
            $columns[$column->{'@attributes'}->name] = new Column($column);
            if($columns[$column->{'@attributes'}->name]->primaryKey) {
                $this->pk = $column->{'@attributes'}->name;
            }
        }
        $this->columns = $columns;
    }

    private function setAssociations(stdClass $schema): void {
        $associations = [];
        if(isset($schema->associations->association)) {
            if(\is_array($schema->associations->association)) {
                foreach ($schema->associations->association as $key => $val) {
                    $associations[ $val->{'@attributes'}->name ] = new Association($val->{'@attributes'}->name, $val);
                }
            } else {
                foreach ($schema->associations as $key => $val) {
                    $associations[ $val->{'@attributes'}->name ] = new Association($val->{'@attributes'}->name, $val);
                }
            }
            
        }
        
        $this->associations = $associations;
    }

}

abstract class SchemaAssociation {
    public const HasOne = "hasone";
    public const HasMany = "hasmany";
    public const BelongsTo = "belongsto";
    public const BelongsToMany = "belongstomany";
}
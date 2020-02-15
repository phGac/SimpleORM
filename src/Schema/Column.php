<?php

namespace Otter\ORM\Schema;

use Otter\ORM\OtterValue;
use Otter\ORM\OtterDefaultValue;

class Column {

    public $type;
    public $defaultValue;
    public $otterDefaultValue;
    public $required;
    public $length;
    public $allowNull;
    public $primaryKey;

    public function __construct($column) {
        $this->type = $column->{'@attributes'}->type;
        $this->required = (isset($column->required)) ? OtterValue::BOOLEAN($column->required) : true;
        $this->length = (isset($column->length)) ? OtterValue::INTEGER($column->length) : OtterValue::UNDEFINED;
        $this->allowNull = (isset($column->{'allow-null'})) ? OtterValue::BOOLEAN($column->{'allow-null'}) : false;
        $this->primaryKey = (isset($column->{'primary-key'})) ? OtterValue::BOOLEAN($column->{'primary-key'}) : false;
        if(isset($column->{'default-value'})) {
            if(isset($column->{'default-value'}->{'@attributes'})) {
                $this->defaultValue = OtterValue::DEFINED;
                $this->otterDefaultValue = $this->otterDefaultValue($column->{'default-value'}->{'@attributes'}->otter);
            } else {
                $this->defaultValue = $column->{'default-value'};
            }
        } else {
            $this->defaultValue = OtterValue::UNDEFINED;
        }
    }

    private function otterDefaultValue(string $otterValue) {
        switch(strtolower($otterValue)) {
            case OtterDefaultValue::OTTER_DATE_NOW:
                return OtterValue::DATE_NOW;
            case OtterDefaultValue::OTTER_DATE_UTC_NOW:
                return OtterValue::DATE_UTC_NOW;
        }
    }

}
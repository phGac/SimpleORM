<?php

namespace Otter\ORM\Schema;

use Otter\ORM\OtterValue;

class Association {

    public $name;
    public $type;
    public $model;
    public $foreignKey;
    public $key;
    public $through;
    public $throughBridge;
    public $throughKey;
    public $strict;

    public function __construct($name, $association) {
        $this->name = $name;
        $this->type = $association->{'@attributes'}->type;
        $this->model = (isset($association->model)) ? $association->model : OtterValue::UNDEFINED;
        $this->foreignKey = (isset($association->{'foreign-key'})) ? $association->{'foreign-key'} : OtterValue::UNDEFINED;
        $this->key = (isset($association->key)) ? $association->key : OtterValue::UNDEFINED;
        $this->through = (isset($association->through)) ? $association->through : OtterValue::UNDEFINED;
        $this->throughBridge = (isset($association->{'through-bridge'})) ? $association->{'through-bridge'} : OtterValue::UNDEFINED;
        $this->throughKey = (isset($association->{'through-key'})) ? $association->{'through-key'} : OtterValue::UNDEFINED;
        $this->strict = (isset($association->strict)) ? OtterValue::BOOLEAN($association->strict) : false;
    }

}
<?php

namespace Otter\ORM\Exception;

use Exception;

class QueryException extends Exception {
    public function __construct(string $message, $any) {
        parent::__construct($message, $any);
        /*echo "<pre>";
        print_r($this);
        echo "</pre>";*/
    }
}
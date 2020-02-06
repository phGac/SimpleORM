<?php

abstract class Common {

    public static function startsWith(string $startString, string $text) {
        $len = strlen($startString);
        return (substr($text, 0, $len) === $startString);
    }

    public static function endsWith(string $endString, string $text) {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($text, -$len) === $endString);
    }

}
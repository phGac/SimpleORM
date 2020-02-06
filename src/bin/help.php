<?php

function help_default() {
    //
}

function help_migration() {
    //
}

function help_sql_to_schema() {
    //
}

function help_new_schema() {
    //
}

return function(string $option) {
    switch(strtoupper($option)) {
        case "MIGRATE":
            help_migration();
        break;
        case "SQL-TO-SCHEMA":
            help_sql_to_schema();
        break;
        case "NEW-SCHEMA":
            help_new_schema();
        break;
        case "DEFAULT":
            help_default();
        break;
        default:
            help_default();
        break;
    }
};

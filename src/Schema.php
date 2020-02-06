<?php

namespace Otter\ORM;

abstract class Schema {
    public static $modelName = '';
    public static $tableName = '';
    public static $columns = [];
    public static $associations = [];
}

class ColumnType {
    public const INT = 'integer';
    public const DECIMAL = 'double';
    public const FLOAT = 'double';
    public const STRING = 'string';
    public const TEXT = 'string';
    public const BOOLEAN = 'boolean';
    public const DATETIME = 'datetime';
    public const DATE = 'date';
    public const MONEY = 'money';
    public const CHAR = 'char';
}

class ColumnDefaultValue {
    public const NOW = 'GETDATE()';
    public const NOW_UTC = 'GETUTCDATE()';
}

class ModelAssociation {
    public const HasOne = "HasOne";
    public const HasMany = "HasMany";
    public const BelongsTo = "BelongsTo";
    public const BelongsToMany = "BelongsToMany";
}

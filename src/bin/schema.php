<?php

class GenerateSchema {
    
    private $modelName;
    private $attributes;
    private $tableName;
    private $schemaInfo;

    public function __construct(string $modelName, array $attributes) {
        $this->modelName = $modelName;
        $this->attributes = $attributes;
    }

    public function new_schema(string $tableName): string {
        $this->tableName = $tableName;

        $columns = '';
        foreach ($this->attributes as $value) {
            $attribute = explode(':', $value);
            $name = $attribute[0];
            $type = $attribute[1];
            
            $columnType = $this->dataType($type);

            $colInfo = "\t\t'$name' => [\n";
            $colInfo .= "\t\t\t'type' => $columnType,\n\t\t],\n";

            $columns .= $colInfo;
        }

        $uses = [
            'Otter\ORM\Schema',
            'Otter\ORM\ColumnType',
            'Otter\ORM\ColumnDefaultValue',
            'Otter\ORM\ModelAssociation',
        ];

        return $this->generate_schema($uses, $columns);
    }

    public function sql_to_schema(string $tableName, array $schemaInfo): string {
        $this->tableName = $tableName;
        $this->schemaInfo = $schemaInfo;
        $PrimaryKey = ($schemaInfo !== null && count($schemaInfo) > 0) ? $schemaInfo['Column_Name'] : null;
        $rows = $this->attributes;

        $columns = '';
        foreach ($rows as $row) {
            $columnName = $row['COLUMN_NAME'];
            $columnType = $this->dataType($row['DATA_TYPE']);
            $max_length = $row['CHARACTER_MAXIMUM_LENGTH'];
            $defaultValue = $this->defaultValue($row['COLUMN_DEFAULT']);
            $allowNull = ($row['IS_NULLABLE'] === 'YES') ? true : false;
            $required = ($allowNull || ($defaultValue !== '' && $defaultValue !== null)) ? 'false' : 'true';

            $colInfo = "\t\t'$columnName' => [\n";
            $colInfo .= "\t\t\t'type' => $columnType,\n";
            $colInfo .= "\t\t\t'required' => $required,\n";
            if($PrimaryKey !== null && $PrimaryKey === $columnName) {
                $colInfo .= "\t\t\t'primaryKey' => true,\n";
            } 
            if($max_length !== null && $max_length !== '') {
                $colInfo .= "\t\t\t'length' => $max_length,\n";
            }
            if($allowNull) {
                $colInfo .= "\t\t\t'allowNull' => true,\n";
            }
            if($defaultValue !== null) {
                $colInfo .= "\t\t\t'defaultValue' => $defaultValue,\n";
            }
            $colInfo .= "\t\t],\n";

            $columns .= $colInfo;
        }

        $uses = [
            'Otter\ORM\Schema',
            'Otter\ORM\ColumnType',
            'Otter\ORM\ColumnDefaultValue',
            'Otter\ORM\ModelAssociation',
        ];

        return $this->generate_schema($uses, $columns);
    }

    private function generate_schema(array $uses, string $columns, string $associations = ''): string {
        $modelName = $this->modelName;
        $tableName = $this->tableName;
        $className = $modelName.'Schema';

        $schema = "<?php\n\n";
        foreach ($uses as $key => $namespace) {
            $schema .= "use $namespace;\n";
        }

        $schema .= "\nclass $className extends Schema {\n";
        $schema .= "\tpublic static \$modelName = '$modelName';\n";
        $schema .= "\tpublic static \$tableName = '$tableName';\n";
        $schema .= "\tpublic static \$columns = [\n$columns\t];\n";
        $schema .= "\tpublic static \$associations = [\n$associations\t];\n";
        $schema .= "}\n\nreturn $className::class;\n";
        
        return $schema;
    }

    private function dataType(string $type) {
        switch(strtolower($type)) {
            // exact numerics
            case "bigint":  return 'ColumnType::BIGINT';
            case "int":     return 'ColumnType::INT';
            case "bit":     return 'ColumnType::BOOLEAN';
            case "decimal": return 'ColumnType::DECIMAL';
            case "money":   return 'ColumnType::MONEY';

            // approximate numerics
            case "float": return 'ColumnType::FLOAT';
            case "real":  return 'ColumnType::FLOAT';

            // date and time
            case "date":            return 'ColumnType::DATE';
            case "datetimeoffset":  return 'ColumnType::DATETIME';
            case "datetime2":       return 'ColumnType::DATETIME';
            case "smalldatetime":   return 'ColumnType::DATETIME';
            case "datetime":        return 'ColumnType::DATETIME';
            case "time":            return 'ColumnType::TIME';

            // character strings
            case "char":    return 'ColumnType::CHAR';
            case "varchar": return 'ColumnType::STRING';
            case "text":    return 'ColumnType::TEXT';

            // unicode character strings
            case "nchar":    return 'ColumnType::CHAR';
            case "nvarchar": return 'ColumnType::STRING';
            case "ntext":    return 'ColumnType::TEXT';

            // binary strings
            case "binary":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "varbinary":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "image":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;

            // other data types
            case "cursor":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "rowvarsion":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "hierarchyid":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "uniqueidentifier":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "sql_variant":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "xml": break;
            case "Spatial Geometry Types":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "Spatial Geografy Types":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
            case "table":
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;

            // generate model
            case "string": return 'ColumnType::STRING';

            default:
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
        }
    }

    private function defaultValue($default) {
        if($default === null || $default === '')
            return null;

        if(Common::startsWith("(N'", $default)) // string
        { 
            $position = (strpos($default, "')")-3);
            $defaultValue = substr($default, 3, $position);
            return $defaultValue;
        }
        else if(Common::startsWith('((', $default)) // number
        {
            $position = (strpos($default, "))")-2);
            $defaultValue = substr($default, 2, $position);
            return $defaultValue;
        }
        else if(Common::endsWith('())', $default)) // function
        { 
            $position = (strpos($default, "())")-1);
            $defaultValue = substr($default, 1, $position);
            switch(strtoupper($defaultValue)) {
                case "GETDATE":
                    return "ColumnDefaultValue::NOW";
                    break;
                case "GETUTCDATE":
                    return "ColumnDefaultValue::NOW_UTC";
                    break;
                default:
                    return "'$defaultValue'";
                    break;
            }
        }
        else {
            return $default;
        }
    }

}
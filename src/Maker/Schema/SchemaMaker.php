<?php

namespace Otter\ORM\Maker\Schema;

use SimpleXMLElement;

class SchemaMaker {

    /**
     * Undocumented variable
     *
     * @var [SimpleXMLElement] xml
     */
    private $xml;

    public function __construct(string $tableName) {
        $this->setXML($tableName);
    }

    private function setXML(string $tableName) {
        $initschema = <<<XML
<?xml version='1.0' encoding='UTF-8'?>
<schema>
    <columns />
    <associations />
</schema>
XML;

        $xml = new SimpleXMLElement($initschema);
        $xml->addAttribute('table', $tableName);
        $this->xml = $xml;
    }

    public function prettyXML(string $xml) {
        $xml = str_replace('><', ">\n<", $xml);
        $xml = str_replace('</columns>', "\t</columns>", $xml);
        $xml = str_replace('<column ', "\t\t<column ", $xml);
        $xml = str_replace('</column>', "\t\t</column>", $xml);
        $xml = str_replace('</associations>', "\t</associations>", $xml);

        $xml = str_replace('<length>', "\t\t\t<length>", $xml);
        $xml = str_replace('<allow-null>', "\t\t\t<allow-null>", $xml);
        $xml = str_replace('<required>', "\t\t\t<required>", $xml);
        $xml = str_replace('<primary-key>', "\t\t\t<primary-key>", $xml);
        $xml = str_replace('<default-value', "\t\t\t<default-value", $xml);

        return $xml;
    }

    public function sql_to_schema(array $rows, string $pk) {
        foreach ($rows as $key => $row) {
            $this->xml->columns->addChild('column');
            $position = -1 +($this->xml->columns->column->count());
            
            $columnName = $row['COLUMN_NAME'];
            $data_type = $this->dataType($row['DATA_TYPE']);
            $max_length = $row['CHARACTER_MAXIMUM_LENGTH'];
            $defaultValue = $this->defaultValue($row['COLUMN_DEFAULT']);
            $allowNull = ($row['IS_NULLABLE'] === 'YES') ? true : false;
            $required = ($allowNull || ($defaultValue !== '' && $defaultValue !== null)) ? 'false' : 'true';

            $this->xml->columns->column[$position]->addAttribute('name', $columnName);
            $this->xml->columns->column[$position]->addAttribute('type', $data_type);
            
            if($pk !== null && $pk === $columnName) {
                $this->xml->columns->column[$position]->addChild('primary-key', 'TRUE');
            }
            if($max_length !== null && $max_length !== '') {
                $this->xml->columns->column[$position]->addChild('length', $max_length);
            }
            if($allowNull) {
                $this->xml->columns->column[$position]->addChild('allow-null', 'TRUE');
            }
            if($defaultValue !== null) {
                if(\preg_match('/otter\..+/', $defaultValue)) {
                    $this->xml->columns->column[$position]->addChild('default-value', '');
                    $this->xml->columns->column[$position]->{'default-value'}->addAttribute('otter', $defaultValue);
                }
                else {
                    $this->xml->columns->column[$position]->addChild('default-value', $defaultValue);
                }
            }
        }
        
        return $this->xml->asXML();
    }

    private function dataType(string $type) {
        switch(strtolower($type)) {
            // exact numerics
            case "bigint":  return 'biginteger';
            case "int":     return 'integer';
            case "bit":     return 'boolean';
            case "decimal": return 'decimal';
            case "money":   return 'money';

            // approximate numerics
            case "float": return 'float';
            case "real":  return 'float';

            // date and time
            case "date":            return 'date';
            case "datetimeoffset":  return 'datetime';
            case "datetime2":       return 'datetime';
            case "smalldatetime":   return 'datetime';
            case "datetime":        return 'datetime';
            case "time":            return 'time';

            // character strings
            case "char":    return 'char';
            case "varchar": return 'string';
            case "text":    return 'text';

            // unicode character strings
            case "nchar":    return 'char';
            case "nvarchar": return 'string';
            case "ntext":    return 'text';

            // binary strings
            case "binary":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "varbinary":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "image":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;

            // other data types
            case "cursor":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "rowvarsion":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "hierarchyid":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "uniqueidentifier":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "sql_variant":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "xml": break;
            case "Spatial Geometry Types":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "Spatial Geografy Types":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;
            case "table":
                echo "Unsopported DATA TYPE [$type] .-";
                exit();
                break;

            default:
                echo "Unsopported DATA_TYPE [$type] .-";
                exit();
                break;
        }
    }

    private function defaultValue($default) {
        if($default === null || $default === '')
            return null;

        if(\preg_match('/\(N\'/', $default)) { //string
            $position = (strpos($default, "')")-3);
            $defaultValue = substr($default, 3, $position);
            return $defaultValue;
        }
        else if(\preg_match('/\(\(/', $default)) // number
        {
            $position = (strpos($default, "))")-2);
            $defaultValue = substr($default, 2, $position);
            return $defaultValue;
        }
        else if(\preg_match('/.+\(\)\)/', $default)) // function
        { 
            $position = (strpos($default, "())")-1);
            $defaultValue = substr($default, 1, $position);
            switch(strtoupper($defaultValue)) {
                case "GETDATE":
                    return \Otter\ORM\OtterDefaultValue::OTTER_DATE_NOW;
                    break;
                case "GETUTCDATE":
                    return \Otter\ORM\OtterDefaultValue::OTTER_UTC_DATE_NOW;
                    break;
                default:
                    return $defaultValue;
                    break;
            }
        }
        else {
            return $default;
        }
    }

}
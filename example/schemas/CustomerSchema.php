<?php

use Otter\ORM\Schema;
use Otter\ORM\ColumnType;
use Otter\ORM\ColumnDefaultValue;
use Otter\ORM\ModelAssociation;

class CustomerSchema extends Schema {
	public static $modelName = 'Customer';
	public static $tableName = 'customers';
	public static $columns = [
		'Id' => [
			'type' => ColumnType::INT,
			'primaryKey' => true,
			'required' => true,
		],
		'FirstName' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'required' => true,
		],
		'LastName' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'required' => true,
		],
		'City' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'allowNull' => true,
			'required' => false,
		],
		'Country' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'allowNull' => true,
			'required' => false,
		],
		'Phone' => [
			'type' => ColumnType::STRING,
			'length' => 20,
			'allowNull' => true,
			'required' => false,
		],
	];
	public static $associations = [];
}

return CustomerSchema::class;

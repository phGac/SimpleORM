<?php

use Otter\ORM\Schema;
use Otter\ORM\ColumnType;
use Otter\ORM\ColumnDefaultValue;
use Otter\ORM\ModelAssociation;

class SupplierSchema extends Schema {
	public static $modelName = 'Supplier';
	public static $tableName = 'suppliers';
	public static $columns = [
		'Id' => [
			'type' => ColumnType::INT,
			'primaryKey' => true,
			'required' => true,
		],
		'CompanyName' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'required' => true,
		],
		'ContactName' => [
			'type' => ColumnType::STRING,
			'length' => 50,
			'allowNull' => true,
			'required' => false,
		],
		'ContactTitle' => [
			'type' => ColumnType::STRING,
			'length' => 40,
			'allowNull' => true,
			'required' => false,
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
			'length' => 30,
			'allowNull' => true,
			'required' => false,
		],
		'Fax' => [
			'type' => ColumnType::STRING,
			'length' => 30,
			'allowNull' => true,
			'required' => false,
		],
	];
	public static $associations = [
		'products' => [
			'type' => ModelAssociation::HasMany,
			'schema' => ProductSchema::class,
			'foreignKey' => 'Id',
			'key' => 'SupplierId'
		],
	];
}

return SupplierSchema::class;

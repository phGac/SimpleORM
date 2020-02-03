<?php

use Otter\ORM\Schema;
use Otter\ORM\ColumnType;
use Otter\ORM\ColumnDefaultValue;
use Otter\ORM\ModelAssociation;

class ProductSchema extends Schema {
	public static $modelName = 'Product';
	public static $tableName = 'products';
	public static $columns = [
		'Id' => [
			'type' => ColumnType::INT,
			'primaryKey' => true,
			'required' => true,
		],
		'ProductName' => [
			'type' => ColumnType::STRING,
			'length' => 50,
			'required' => true,
		],
		'SupplierId' => [
			'type' => ColumnType::INT,
			'required' => true,
		],
		'UnitPrice' => [
			'type' => ColumnType::DECIMAL,
			'allowNull' => true,
			'defaultValue' => 0,
			'required' => false,
		],
		'Package' => [
			'type' => ColumnType::STRING,
			'length' => 30,
			'allowNull' => true,
			'required' => false,
		],
		'IsDiscontinued' => [
			'type' => ColumnType::BOOLEAN,
			'defaultValue' => 0,
			'required' => false,
		],
	];
	public static $associations = [
		'supplier' => [
			'type' => ModelAssociation::BelongsTo,
			'schema' => SupplierSchema::class,
			'foreignKey' => 'SupplierId',
			'key' => 'Id',
		],
		'orders' => [
			'type' => ModelAssociation::BelongsToMany,
			'schema' => OrderSchema::class,
			'through' => OrderItemSchema::class,
			'foreignKey' => 'id', // this schema (Product)
			'throughForeignKey' => 'ProductId', // through schema foreignkey (OrderItem)
			'throughKey' => 'OrderId', // through schema foreignkey (OrderItem)
			'key' => 'id', // final schema (Order)
			'strict' => true, // ==> OPTIONAL!! (this uses INNER JOIN instead of LEFT JOIN)
		],
	];
}

return ProductSchema::class;

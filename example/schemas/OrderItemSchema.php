<?php

use Otter\ORM\Schema;
use Otter\ORM\ColumnType;
use Otter\ORM\ColumnDefaultValue;
use Otter\ORM\ModelAssociation;

class OrderItemSchema extends Schema {
	public static $modelName = 'OrderItem';
	public static $tableName = 'orderItems';
	public static $columns = [
		'Id' => [
			'type' => ColumnType::INT,
			'primaryKey' => true,
			'required' => true,
		],
		'OrderId' => [
			'type' => ColumnType::INT,
			'required' => true,
		],
		'ProductId' => [
			'type' => ColumnType::INT,
			'required' => true,
		],
		'UnitPrice' => [
			'type' => ColumnType::DECIMAL,
			'defaultValue' => 0,
			'required' => false,
		],
		'Quantity' => [
			'type' => ColumnType::INT,
			'defaultValue' => 1,
			'required' => false,
		],
	];
	public static $associations = [
		'orders' => [
			'type' => ModelAssociation::HasMany,
			'schema' => OrderSchema::class,
			'foreignKey' => 'OrderId', // this schema (OrderItem)
			'key' => 'id' // another schema (Order)
		],
		'products' => [
			'type' => ModelAssociation::HasMany,
			'schema' => ProductSchema::class,
			'foreignKey' => 'ProductId', // this schema (OrderItem)
			'key' => 'id' // another schema (Product)
		],
	];
}

return OrderItemSchema::class;

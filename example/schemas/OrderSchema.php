<?php

use Otter\ORM\Schema;
use Otter\ORM\ColumnType;
use Otter\ORM\ColumnDefaultValue;
use Otter\ORM\ModelAssociation;

class OrderSchema extends Schema {
	public static $modelName = 'Order';
	public static $tableName = 'orders';
	public static $columns = [
		'Id' => [
			'type' => ColumnType::INT,
			'primaryKey' => true,
			'required' => true,
		],
		'OrderDate' => [
			'type' => ColumnType::DATETIME,
			'defaultValue' => ColumnDefaultValue::NOW,
			'required' => false,
		],
		'OrderNumber' => [
			'type' => ColumnType::STRING,
			'length' => 10,
			'allowNull' => true,
			'required' => false,
		],
		'CustomerId' => [
			'type' => ColumnType::INT,
			'required' => true,
		],
		'TotalAmount' => [
			'type' => ColumnType::DECIMAL,
			'allowNull' => true,
			'defaultValue' => 0,
			'required' => false,
		],
	];
	public static $associations = [
		'products' => [
			'type' => ModelAssociation::BelongsToMany,
			'schema' => ProductSchema::class,
			'through' => OrderItemSchema::class,
			'foreignKey' => 'id', // this schema (Order)
			'throughForeignKey' => 'OrderId', // through schema foreignkey (OrderItem)
			'throughKey' => 'ProductId', // through schema foreignkey (OrderItem)
			'key' => 'id', // final schema (Product)
			'strict' => true, // ==> OPTIONAL!! (this uses INNER JOIN instead of LEFT JOIN)
		],
	];
}

return OrderSchema::class;

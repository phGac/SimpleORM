<?php

require_once __DIR__.'/../autoload.php';

use \Otter\ORM\Otter;

$orm = new Otter('localhost', 'store', 'sa', 'bimmer');
$orm->schemas(__DIR__.'/schemas');

/*
# Database used:
# https://www.dofactory.com/sql/sample-database

insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (1, 1, 1200, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (2, 2, 3000, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (2, 2, 1500, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (3, 1, 1200, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (3, 3, 500, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (1, 1, 780, 1);
insert into OrderItem (OrderId, ProductId, UnitPrice, Quantity) values (4, 4, 4000, 1);
*/

$Supplier = Otter::get('Supplier');
$OrderItem = Otter::get('OrderItem');
$Product = Otter::get('Product');
$Order = Otter::get('Order');
$Customer = Otter::get('Customer');

$customers = $Customer->findAll()
                    ->include(['orders', 'orders.products'])
                    //->limit(5)
                    ->pagination(1, 10)
                    ->end();
echo "<pre>";
print_r($customers);
echo "</pre>";
/*
$count = $Order->count()
               ->where([
                   'TotalAmount' => ['>', 2000]
               ])
               ->end();

echo "count: $count";
*/
/*
$result = $Order->delete([ 'Id' => 24 ]);

if($result) {
echo "deleted ok.";
} else {
echo ":c";
}
*/
/*
$result = $Order->update([
                    'OrderNumber' => 160873,
                    'CustomerId' => 4,
                    'TotalAmount' => 1050,
                ],[
                    'Id' => 24
                ]);
if($result) {
    echo "Order updated.";
} else {
    echo ":c";
}
*/
/*
$result = $Order->create([
                    'OrderNumber' => 115,
                    'CustomerId' => 2,
                    'TotalAmount' => 2200,
                ]);
if($result) {
    echo "Order created.";
} else {
    echo ":c";
}
*/
/*
$orders = $Order->findAll()
                    ->include([ 'products' ])
                    ->limit(10)
                    ->end();

echo "<pre>";
print_r($orders);
echo "</pre>";
*/
/*
$products = $Product->findAll()
                    ->include([ 'orders' ])
                    ->limit(10)
                    ->end();

echo "<pre>";
print_r($products);
echo "</pre>";
*/
/*
$ordersitems = $OrderItem->findAll()
                    ->include([ 'order', 'product' ])
                    //->limit(10)
                    ->end();

echo "<pre>";
print_r($ordersitems);
echo "</pre>";
*/
/*
$products = $Product->findAll()
                    ->include([ 'supplier' ])
                    ->limit(10)
                    ->end();

echo "<pre>";
print_r($products);
echo "</pre>";
*/
/*
$orders = $Order->findAll()
                ->where([
                    'OrderDate' => [ '>', '2010-07-01' ], // YYYY-MM-DD or YYYY
                ])
                ->end();

echo "<pre>";
print_r($orders);
echo "</pre>";
*/
/*
$suppliers = $Supplier->findAll()
                    ->include([ 'products' ])
                    //->where([ 'CompanyName' => ['LIKE', "%AC%"], 'products.Id' => 1 ])
                    ->limit(10)
                    ->end();

echo "<pre>";
print_r($suppliers);
*/

echo "<br>Last Query:";
echo "<p>".Otter::$lastQuery."</p>";
echo "<br>Last Error:<pre>";
print_r(Otter::$lastQueryErrorInfo);
echo "</pre>";

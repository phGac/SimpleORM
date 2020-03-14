<?php

require_once __DIR__.'/../autoload.php';

use \Otter\ORM\Otter;
use \Otter\ORM\OtterValue;
use \Otter\ORM\OtterWhere;

$orm = new Otter('localhost', 'store', 'sa', 'passWORD.123');
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
                    ->where(
                        OtterWhere::AND(
                            OtterWhere::condition('Id', 1),
                            OtterWhere::OR(
                                OtterWhere::condition('FirstName', 'Maria', 'LIKE'),
                                OtterWhere::condition('FirstName', 'Philippe', 'LIKE')
                            ),
                            OtterWhere::BETWEEN('Id', 1, 5),
                            OtterWhere::IN('Id', 1, 2, 3, 4)
                        )
                    )
                    ->end();

echo "<pre>";
print_r($customers);
echo "</pre>";


echo "<br>Last Query:";
echo "<p>".Otter::$lastQuery."</p>";
echo "<br>Last Error:<pre>";
print_r(Otter::$lastQueryErrorInfo);
echo "</pre>";


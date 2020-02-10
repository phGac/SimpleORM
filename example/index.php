<?php

# Database used:
# https://www.dofactory.com/sql/sample-database

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__."/../vendor/autoload.php";

$orm = new \Otter\ORM\SimpleORM('localhost', 'store', 'sa', 'santiagosur');//passWORD.123
$orm->schemas(__DIR__.'/schemas');


$Product = \Otter\ORM\SimpleORM::get('Product');
$Order = \Otter\ORM\SimpleORM::get('Order');
$Supplier = \Otter\ORM\SimpleORM::get('Supplier');


$orders = $Order->find([ 'Id', 'TotalAmount' ])
                ->end();

if($orders !== null) {
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
} else {
    $info = \Otter\ORM\SimpleORM::lastQueryErrorInfo(); // array
    print_r($info);
}

/*
$count = $Order->count()
                ->join([ 'Order.products' ])
                ->end();
echo "<pre>";
print_r($count);
echo "</pre>";
*/
/*
$orders = $Order->findAll([ 'Id' ])
                ->join([ 'Order.products' ])
                ->pagination(1, 10)
                ->end();

if($orders !== null) {
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
} else {
    $info = \Otter\ORM\SimpleORM::lastQueryErrorInfo(); // array
    print_r($info);
}
*/
/*
$products = $Product->findAll()
                    ->join([ 'Product.orders' ])
                    ->limit(3)
                    ->end();
echo "<pre>";
print_r($products);
echo "</pre>";
*/
/*
$products = $Product
                ->findAll()
                ->where([
                    'Product.Id' => ['>', 1],
                    'Product.IsDiscontinued' => true,
                ])
                ->join([ 'Product.supplier' ])
                ->limit(5)
                ->orderBy([
                    'Product.Id', // => 'ASC'
                    'Product.IsDiscontinued' => 'DESC'
                ])
                //->groupBy([ 'Product.Id' ])
                ->end();

echo "<pre>";
print_r($products);
echo "</pre>";
*/
/*
$suppliers = $Supplier
                    ->findAll()
                    ->join([ 'Supplier.products' ])
                    ->limit(5)
                    ->where([ 'products.IsDiscontinued' => true ])
                    ->end();
echo "<pre>";
print_r($suppliers);
echo "</pre>";
*/

/*
$products = $Product->findAll()
                    ->join([ 'Product.supplier' ])
                    ->where([ 'Product.IsDiscontinued' => true ])
                    ->end();


echo "<pre>";
print_r($products);
echo "</pre>";
*/
echo "Last Query: ".\Otter\ORM\SimpleORM::lastQuery();

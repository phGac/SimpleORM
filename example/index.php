<?php

# Database used:
# https://www.dofactory.com/sql/sample-database

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__."/../loader.php";

$orm = new \Otter\ORM\SimpleORM('localhost', 'store', 'sa', '');
$orm->schemas(__DIR__.'/schemas');


$Product = \Otter\ORM\SimpleORM::get('Product');
$Order = \Otter\ORM\SimpleORM::get('Order');
$Supplier = \Otter\ORM\SimpleORM::get('Supplier');

$orders = $Order->findAll()
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

/*
$products = $Product->findAll()
                    ->join([ 'Product.orders' ])
                    //->limit(3)
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
echo "Last Query: ".\Otter\ORM\SimpleORM::lastQuery();

/*
$Supplier = unserialize(Supplier);
$Product = unserialize(Product);

$products = $Product->selectAll([
    'include' => [ 'supplier' ],
    'where' => [
        'isDiscontinued' => true
    ]
]);

echo "<pre>";
foreach ($products as $key => $product) {
    echo $product;
}
echo "</pre>";

echo $Product->getLastQuery();
*/
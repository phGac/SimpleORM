# SimpleORM

This is a simple ORM with PHP for SQL Server without dependencies.

- [installation](#Installation)
- [requeriments](#Requeriments)
- [SELECT](#SELECT)
- [CREATE](#CREATE)
- [UPDATE](#UPDATE)
- [DELETE](#DELETE)

## Installation

...

## Requeriments

| Requeriment | Version | Info |
| ----------- | ------- | ---- |
| PHP | 7.0^ | - |
| PDO | - | - |
| PDO_SQLSRV | 4.0^ | It depends on the PHP version |

[download and configure PDO_SQLSRV](https://docs.microsoft.com/en-us/sql/connect/php/example-application-pdo-sqlsrv-driver "download and configure PDO_SQLSRV")

## How to Use

    $orm = new \Otter\ORM\SimpleORM('localhost', 'databaseName', 'sa', 'password');
    $orm->schemas(__DIR__.'/schemas');

    $User = \Otter\ORM\SimpleORM::get('User');

    $users = $User->findAll()
                  ->end();

    if($users !== null) {
        print_r($users); // array of objects
    } else {
        $info = \Otter\ORM\SimpleORM::lastQueryErrorInfo(); // array
        print_r($info);
    }

## Configuration

We need an folder with schemas of database.

    $orm->schemas(__DIR__.'/schemas'); // path to configuration files schemas

example

    <?php

    use Otter\ORM\Schema;
    use Otter\ORM\ColumnType;
    use Otter\ORM\ColumnDefaultValue;
    use Otter\ORM\ModelAssociation;

    class UserSchema extends Schema {
        public static $modelName = 'User'; // Identification name
        public static $tableName = 'users'; // name in database
        public static $columns = [ // columns in database
            'id' => [
                'type' => ColumnsType::INT,
                'primaryKey' => true,
                'required' => true
            ],
            'name' => [
                'type' => ColumnType::STRING,
                'length' => 40,
                'required' => true,
            ],
            'email' => [
                'type' => ColumnType::STRING,
                'allowNull' => false,
                'required' => false,
                'defaultValue' => 'email@email.com',
            ],
            'country' => [
                'type' => ColumnType::STRING,
                'allowNull' => true,
                'required' => true,
            ]
        ];
        public static $associations = [];
    }

    return UserSchema::class; // Very important!!

Not forget the return class, it is very important for internal operation

### Auto Generate Schemas (Terminal)

If your database is generated you can use the command line to generate the schemas.

example

    > php bin/otter generate:schema:db --host=localhost --db=databaseName --user=sa --password=password123 --path=db/schemas --model=User --table=users

#### Arguments

| argument | info | example value |
| -------- | ---- | ------------- |
| --host | The host of database | 127.0.0.1 |
| --db | The name of database to use | db_disks |
| --user | The username to login | sa |
| --password | The password of user login | password123 |
| --path | The to save the schema | db/schema |
| --model | The model name | User |
| --table | The table name in database | users |

## Queries

### SELECT

    $users = $User->find()
                  ->end();

    $users = $User->findAll()
                  ->end();

| Method | info | Options | example |
| ------ | ---- | ------- | ------- |
| find | Return the first result | onlyColumns [array] | find([ 'id', 'name', 'role.name' ]) |
| findAll | Return all results | onlyColumns [array] | find([ 'id', 'name', 'role.name' ]) |

#### Filter Results

This find all users that the name is **George**:

    $User->findAll()
         ->where([
             'User.name' => 'George'
         ])
         ->end();

This find first users that the name is **George** or **Philippe**:

    $User->find()
         ->where([
             'User.name' => 'George',
             'OR',
             'User.name' => 'Philippe',
         ])
         ->end();

This find first users that the country is **EEUU**, name starts with **Ge** and id is more than 10:

    $User->find()
         ->where([
             'User.country' => 'EEUU',
             'User.name' => ['LIKE', 'GE%'],
             'User.id' => ['>', 10]
         ])
         ->end();

Get top 10 results:

    $User->findAll()
         ->limit(10)
         ->end();

Get only id and name:

    $User->findAll([
            'id',
            'name'
        ])
        ->end();

Order by:

    $User->findAll([
            'id',
            'name'
        ])
        ->orderBy([
            'User.id',              // Ascendent
            'User.name' => 'DESC'   // Descendent
        ])
        ->end();

Group by:

    $User->findAll([
            'country'
        ])
        ->groupBy([
            'User.country'
        ])
        ->end();

Relations:

    $User->find()
         ->join([
             'User.role'
         ])
         ->end();

- The join dependens of the configuration of schemas.

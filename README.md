# SimpleORM

This is a simple ORM with PHP for SQL Server without dependencies.

- [installation](#Installation)
  - [requeriments](#Requeriments)
- [How to Use](#How-to-Use)
  - [SELECT](#SELECT)
  - [CREATE](#CREATE)
  - [UPDATE](#UPDATE)
  - [DELETE](#DELETE)
- [Relationships](#Relationships)
  - [Belongs To](#Belongs-To)
  - [Belongs To Many](#Belongs-To-Many)
  - [Has One](#Has-One)
  - [Has Many](#Has-Many)
- [Libre Query](#Libre-Query)

## Installation

...

### Requeriments

| Requeriment | Version | Info |
| ----------- | ------- | ---- |
| PHP | 7.0^ | - |
| PDO_SQLSRV | 4.0^ | It depends on the PHP version |

[download and configure PDO_SQLSRV](https://docs.microsoft.com/en-us/sql/connect/php/example-application-pdo-sqlsrv-driver "download and configure PDO_SQLSRV")

## How to Use

    $orm = new \Otter\ORM\Otter('localhost', 'databaseName', 'sa', 'password');
    $orm->schemas(__DIR__.'/schemas');

    $User = \Otter\ORM\Otter::get('User');

    $users = $User->findAll()
                  ->end();

    if($users !== null) {
        print_r($users); // array of objects
    } else {
        $info = \Otter\ORM\Otter::lastQueryErrorInfo(); // array
        print_r($info);
    }

## Configuration

We need an folder with schemas of database.

    $orm->schemas(__DIR__.'/schemas'); // path to configuration files schemas

schema example ( schemas/UserSchema.xml )

    <?xml version="1.0" encoding="UTF-8"?>
    <schema table="users">
        <columns>
            <column name="id" type="integer">
                <primary-key>TRUE</primary-key>
                <allow-null>FALSE</allow-null>
                <required>FALSE</required>
            </column>
            <column name="id_role" type="integer">
                <default-value>1</default-value>
            </column>
            <column name="name" type="string">
                <length>100</length>
            </column>
            <column name="email" type="string">
                <length>100</length>
                <default-value>email@email.com</default-value>
            </column>
            <column name="country" type="string">
                <allow-null>TRUE</allow-null>
                <required>TRUE</required>
            </column>
            <column name="createdAt" type="datetime">
                <allow-null>FALSE</allow-null>
                <required>TRUE</required>
                <default-value otter="otter.date.now"></default-value>
            </column>
            <column name="updatedAt" type="datetime">
                <allow-null>TRUE</allow-null>
                <required>FALSE</required>
            </column>
        </columns>
        <associations/>
    </schema>

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

Simple examples

    $users = $User->find()
                  ->end();

    $users = $User->findAll()
                  ->end();

| Method | info | Options | example |
| ------ | ---- | ------- | ------- |
| find | Return the first result | onlyColumns [array] | find([ 'id', 'name', 'role.name' ]) |
| findAll | Return all results | onlyColumns [array] | find([ 'id', 'name', 'role.name' ]) |

- The `find(...)` method is an alias for `findAll(...)->limit(1)`

#### Filter Results

This find all users that the name is **George**:

    $User->findAll()
         ->where([
             'name' => 'George'
         ])
         ->end();

This find first users that the name is **George** or **Philippe**:

    $User->find()
         ->where([
             'name' => 'George',
             'OR',
             'name' => 'Philippe',
         ])
         ->end();

This find first users that the country is **EEUU**, name starts with **Ge** and id is more than 10:

    $User->find()
         ->where([
             'country' => 'EEUU',
             'name' => ['LIKE', 'GE%'],
             'id' => ['>', 10]
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
            'id',              // Ascendent
            'name' => 'DESC'   // Descendent
        ])
        ->end();

Group by:

    $User->findAll([
            'country'
        ])
        ->groupBy([
            'country'
        ])
        ->end();

Count data:

    $User->count()
         ->where([
            'country' => 'EEUU'
         ])
         ->end();

- This returns a number

Relations:

    $User->find()
         ->include([
             'role'
         ])
         ->end();

- The join internals uses dependens of the configuration of schemas.
- [Go to relations for more details](#Relations)

### CREATE

    $User->create([
        'name' => 'Joe',
        'country' => 'France',
    ]);

- This return a **boolean**
- No uses the end function
- Remember: We uses the configuration file. If a column is required and not passed, `DefaultValue` will be used or __NULL__ will be used if allowed `AllowNull`

schemas/UserSchema.php

    ...
    public static $columns = [
        ...
        'email' => [
            'type' => ColumnType::STRING,
            'allowNull' => false,
            'required' => false,
            'defaultValue' => 'email@email.com', // will be used
        ],
        ...
    ];
    ...

### UPDATE

    $User->update([
        'country' => 'EEUU',
    ])
    ->where([
        'id' => 1
    ])
    ->end();

- **Don't forget** the `where` function if you don't want to update all the data in the table.

### DELETE

    $User->update([
        'country' => 'EEUU',
    ])
    ->where([
        'id' => 1
    ])
    ->end();

- **Don't forget** the `where` function if you don't want to delete all the data in the table.

## Relationships

We uses 4 types of relations:

- BelongsTo
- BelongsToMany
- HasOne
- HasMany

### Belongs To

This is used for a relation like 1:1 or 1:1. For example, a user can have one or more roles (depending on the design). In both cases, we use BelongsTo in the user scheme.

schemas/UserSchema.php

    ...
    public static $associations = [
        'role' => [                     // <= association nickname
            'type' => ModelAssociation::BelongsTo,
            'schema' => RoleSchema::class, // Internal use. Very important
            'foreignKey' => 'id_role',  // Foreign Key (UserSchema)
            'key' => 'id',               // Key (RoleSchema)
            'strict' => true,           // optional
        ],
        ...
    ];
    ...

- The `strict` option force to use Inner Join in select. By default we use _LEFT JOIN_

### Belongs To Many

This is used for a relation like N:1.

schemas/UserSchema.php

    ...
    public static $associations = [
        'role' => [                     // <= association nickname
            'type' => ModelAssociation::BelongsToMany,
            'schema' => RoleSchema::class, // Internal use. Very important
            'foreignKey' => 'id_role',  // Foreign Key (UserSchema)
            'key' => 'id',               // Key (RoleSchema)
            'strict' => true,           // optional
        ],
        ...
    ];
    ...

- The `strict` option force to use Inner Join in select. By default we use _LEFT JOIN_

### Has One

This is used for a relation like 1:1. For example, a book have one author (usually).

schemas/BookSchema.php

    ...
    public static $associations = [
        'author' => [                     // <= association nickname
            'type' => ModelAssociation::HasOne,
            'schema' => AuthorSchema::class, // Internal use. Very important
            'foreignKey' => 'id_author',  // Foreign Key (UserSchema)
            'key' => 'id',               // Key (RoleSchema)
            'strict' => true,           // optional
        ],
        ...
    ];
    ...

- The `strict` option force to use Inner Join in select. By default we use _LEFT JOIN_

### Has Many

This is used for a relation like 1:N. For example, a book have one author (usually).

schemas/BookSchema.php

    ...
    public static $associations = [
        'author' => [                     // <= association nickname
            'type' => ModelAssociation::HasOne,
            'schema' => AuthorSchema::class, // Internal use. Very important
            'foreignKey' => 'id_author',  // Foreign Key (BookSchema)
            'key' => 'id',               // Key (AuthorSchema)
            'strict' => true,           // optional
        ],
        ...
    ];
    ...

- The `strict` option force to use Inner Join in select. By default we use _LEFT JOIN_

## Libre Query

If you want, you can generate a query an execute.

    Otter\ORM\Otter::db("SELECT * FROM persons");

- Returns `array|null`

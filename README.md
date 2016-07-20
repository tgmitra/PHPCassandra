## PHPCassandra - The Simple Cassandra PHP client library

PHPCassandra is a PHP Cassandra library that lets you get more done with fewer lines of code. This is faster and lightweight client library, you dont have to remember all Cassandra command when working with PHP, just call related PHPCassandra function and pass required parameters and you are done.

## Requirements

1. PHP 5.3 or greater

## Installation

Before you install CassandraPHP do the following 

    1. Make sure java is installed.

    2. Download casandra from http://www.planetcassandra.org/cassandra/ and install.

    3. Install DataStax PHP Driver for Apache Cassandra from https://datastax.github.io/php-driver/

Now drag and drop the **/CassandraPHP** and **example.php** (optional) files into your application's directories. Now to utilize this class, first import PHPCassandra into your project, and require it.
```
require_once 'CassandraPHP/CassandraPHP.Class.php';
```

## Initialization

Simple initialization:
```
$Cassandra = new CassandraPHP();
```

### Handle keyspace

Before you start anything its better to create your keyspace, to do this just call the following code to create keyspace with 'member' name and 'SimpleStrategy' replication class
```
$Cassandra->Keyspace()->Create('main', 'SimpleStrategy');
```

Now once you are sucessfully create keyspace, you can list the available keyspace with this 
```
$ResultList = $Cassandra->Keyspace()->GetList();
```

Now you are good to go with `main` keyspace, just connect the same with this 
```
$Cassandra->ConnectCluster( 'members' );
```

### Create Table

Now create table user_master with userId as primary key
```
$Cassandra->Table()->Create('user_master', array(
    'user_id' => 'int PRIMARY KEY',
    'user_name' => 'text',
    'user_city' => 'text',
	'email' => 'list<text>',
    'user_sal' => 'varint',
    'user_phone' => 'varint'
));
```

Well, now the table is created, your job is to add an index on it, this will create a index named as `name` on table: `userMaster`, field name: `userName`
```
$Cassandra->Table()->CreateIndex('name', 'user_master', 'user_name');
```

Incase you want to drop the index you have created, then do the following
```
$Cassandra->Table()->DropIndex('name');
```

Not just index, you may drop table also
```
$Cassandra->Table()->DropTable('user_master');
```

Now its time to insert some data on table: user_master, lets do the following to insert a row 
```
$Cassandra->Table()->Insert('user_master', array('user_id'=>2, 'user_name'=>'Julian Diaz', 'user_city'=>'Vienna'));
```

Or use below to insert a row along a list item available in row, for example `email` is used below as list
```
$Cassandra->Table->Insert('user_master', array('name'=>'Alex Dorner', 'email'=>array('alex@example.com')));
```

Now update a row with this
```
$Cassandra->Table()->Update('user_master', array('user_name'=>'Hubert Farnsworth'), array('user_id'=>2));
```

Now the table is ready with some data inside, so lets run a select query and see what happned
```
$Result = $Cassandra->Table()->Select('*', 'monthly_high', array(array('user', '=', 'tjake')), 1);
```

or if you like run the raw select query also
```
$Result = $Cassandra->Table()->ExecSelectQuery("SELECT * FROM monthly_high LIMIT 10");
```

Both of the above query load the table data into a PHP array.

After select let say you want to delete a row
```
$Cassandra->Table()->Delete('user_master', array('user_id' => 2));
```

In cassandra its possible to delete a single cell instead of deleting entire row, the below method will remove the cell data for user_name and user_city, leaving the other data of same cell intact
```
$Cassandra->Table()->Delete('user_master', array('user_id' => 2), array('user_name', 'user_city'));
```

If you like you can execute batch statement on Cassandra as well, please note only insert, update and delete is allowed on batch statement
```
$Cassandra->Table()->Batch(array(
    array('INSERT' => array('user_master', array('user_id' => 3, 'user_name'=>'Robert Braunstein', 'user_city'=>'Paris'))),
    array('INSERT' => array('user_master', array('user_id' => 4, 'user_name'=>'Nicholas Braun', 'user_city'=>'Rome'))),
    array('UPDATE' => array('user_master', array('user_name'=>'Miriam Downey'), array('user_id' => 1))),
    array('DELETE' => array('user_master', array('user_id' => 2), array('user_city'))),
), 'BATCH_LOGGED');
```

or create a materialized view

```
$Cassandra->Table()->CreateView(
    'monthly_high', 
    'scores', 
    array('user', 'score'), 
    array(
        array('game', 'IS NOT NULL', '', 'AND'),
        array('year', 'IS NOT NULL', '', 'AND'),
        array('month', 'IS NOT NULL', '', 'AND'),
        array('score', 'IS NOT NULL', '', 'AND'),
        array('user', 'IS NOT NULL', '', 'AND'),
        array('day', 'IS NOT NULL', '', 'AND'),
    ), 
    array('user', 'score', 'game', 'year', 'month', 'day'), 
    'CLUSTERING', 'score', 'DESC');
```



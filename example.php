<?php
// The Simple Cassandra PHP client library
require_once 'CassandraPHP/CassandraPHP.Class.php';

/* Create instance of Cassandra PHP Library*/
$Cassandra = new CassandraPHP();

/* Conect with keyspace member */
$Cassandra->ConnectCluster( 'members' );

/* Create Keyspace */
$Cassandra->Keyspace()->Create('main', 'SimpleStrategy');

/* Get list of keyspaces */
$Result = $Cassandra->Keyspace()->GetList();

/* Create Table */
$Cassandra->Table()->Create('userMaster', array(
    'user_id' => 'int PRIMARY KEY',
    'user_name' => 'text',
    'user_city' => 'text',
    'user_sal' => 'varint',
    'user_phone' => 'varint'
));

/* Create index inside table */
$Cassandra->Table()->CreateIndex('name', 'user_master', 'user_name');

/* Delete index */
$Cassandra->Table()->DropIndex('name');

/* Drop Table */
$Cassandra->Table()->DropTable('scores');

/* Insert statement for table, also here the field email is a list, so this field can accept multiple values  */
$Cassandra->Table->Insert('user_master', array('name'=>'Alex Dorner', 'email'=>array('alex@example.com')));

/* Simple Data Insert */
$Cassandra->Table()->Insert('user_master', array('user_id'=>2, 'user_name'=>'Julian Diaz', 'user_city'=>'Vienna'));

/* Update table data */
$Cassandra->Table()->Update('user_master', array('user_name'=>'Hubert Farnsworth'), array('user_id'=>2));

/* Select table data and oad into an array */
$Result = $Cassandra->Table()->Select('*', 'user_master');

/* Select table data with filter option */
$Result = $Cassandra->Table()->Select('*', 'monthly_high', array(array('user', '=', 'tjake')), 1);

/* Execute raw select query */
$Result = $Cassandra->Table()->ExecSelectQuery("SELECT * FROM monthly_high LIMIT 10");

/* Delete specific item from rows of cassandra table */
$Cassandra->Table()->Delete('user_master', array('user_id' => 2), array('user_name', 'user_city'));

/* Delete entire row of Cassandra table */
$Cassandra->Table()->Delete('user_master', array('user_id' => 2));


/* Execute batch statement, please note only insert, update and delete is allowed on batch statement */
$Cassandra->Table()->Batch(array(
    array('INSERT' => array('user_master', array('user_id' => 3, 'user_name'=>'Robert Braunstein', 'user_city'=>'Paris'))),
    array('INSERT' => array('user_master', array('user_id' => 4, 'user_name'=>'Nicholas Braun', 'user_city'=>'Rome'))),
    array('UPDATE' => array('user_master', array('user_name'=>'Miriam Downey'), array('user_id' => 1))),
    array('DELETE' => array('user_master', array('user_id' => 2), array('user_city'))),
), 'BATCH_LOGGED');

/* Create View of table */
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
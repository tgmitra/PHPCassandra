<?php
require_once __DIR__.'/BaseMethods.Class.php';
require_once __DIR__.'/KeyspaceMethods.Class.php';
require_once __DIR__.'/TableMethods.Class.php';

class CassandraPHP extends BaseMethods {
    protected $cluster;
    public $session, $BatchMode=false;

    public function __construct($options = false) {
        if (!extension_loaded('cassandra')) die("Unable to load cassandra extension\n");
        
        $this->CassandraConnect($options);
    }
    
    /**
     * Create session, optionally scoped to a keyspace
     * @param type $keyspace
     * @return boolean
     */
    public function ConnectCluster( $keyspace = '' ) {
        if($keyspace == '')
            $this->session = $this->cluster->connect( );
        else
            $this->session = $this->cluster->connect( $keyspace );
    }
    
    /**
     * Call Keyspace related menthods
     * @return \KeyspaceMethods
     */
    public function Keyspace() {
        return new KeyspaceMethods($this);
    }

    /**
     * Call Table related methods
     * @return \TableMethods
     */
    public function Table() {
        return new TableMethods( $this );
    }

    /**
     * Call Columnfamily related methods which is same as table
     * @return \TableMethods
     */
    public function Columnfamily() {
        return new TableMethods( $this );
    }

    /**
     * Drop methods
     * @return \DropMethods
     */
    public function Drop() {
        return new DropMethods( $this );
    }
}
?>
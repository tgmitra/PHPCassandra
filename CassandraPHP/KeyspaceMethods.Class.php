<?php

class KeyspaceMethods {
    private $CassandraPHP;
    
    public function __construct($cPHP) {
        $this->CassandraPHP = $cPHP;
    }
       
    /**
     * Create keyspace
     * @param type $KeyspaceName
     * @param type $ReplicationClass
     * @param type $ReplicationFactor
     * @return type
     */
    public function Create($KeyspaceName, $ReplicationClass='', $ReplicationFactor=1) {
        $CreateStatement = "CREATE KEYSPACE ".$KeyspaceName." WITH replication = "
                . "{'class':'".$ReplicationClass."', 'replication_factor':".$ReplicationFactor."};";   
        return $this->CassandraPHP->exec( $CreateStatement, 'SIMPLE_STATEMENT' );
    }

    /**
     * Get list of keyspace
     * @return type
     */
    public function GetList() {
        $Schema = $this->CassandraPHP->session->schema();
        $KeyspaceDetails = array();
        foreach ($Schema->keyspaces() as $keyspace) {
            $KeyspaceDetails[] = array(
                'KeyspaceName'          => $keyspace->name(),
                'ReplicationClassName'  => $keyspace->replicationClassName(),
                'DurableWrites'  => $keyspace->hasDurableWrites() ? 'true' : 'false',
            );
        }
        return $KeyspaceDetails;
    }
    
}
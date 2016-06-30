<?php

class BaseMethods {
    
    /**
     * Connect with Cassandra
     * @param type $options
     */
    protected function CassandraConnect($options = false) {
        $conn = Cassandra::cluster();
        
        // Specifying addresses of Cassandra nodes
        if(isset($options['ContactPoints'])) {
            $conn = $conn->withContactPoints(
                $options['ContactPoints']['ContactIP'], 
                $options['ContactPoints']['ContactDomain'], 
                $options['ContactPoints']['ContactHost']
            );
            
            if(isset($options['ContactPoints']['ContactPort'])) 
                $conn = $conn->withPort( $options['ContactPoints']['ContactPort'] );
        }
        
        // Persistent sessions
        if(isset($options['IsPersistentSession'])) 
            $conn = $conn->withPersistentSessions( $options['IsPersistentSession'] );
        
        // Configuring load balancing policy
        if(isset($options['IsTokenAwareRouting'])) 
            $conn = $conn->withTokenAwareRouting( $options['IsTokenAwareRouting'] );
        
        // Add load balancing policy
        if(isset($options['LoadBalancingPolicy'])) {
            if(isset($options['LoadBalancingPolicy']['Location'])) {
                $conn = $conn->withDatacenterAwareRoundRobinLoadBalancingPolicy(
                        $options['LoadBalancingPolicy']['Location'], 
                        $options['LoadBalancingPolicy']['HostNumber'], 
                        $options['LoadBalancingPolicy']['IsLocal']
                   );
            }
            else 
                $conn = $conn->withDatacenterAwareRoundRobinLoadBalancingPolicy();
        }
        
        // Add LatencyRouting
        if(isset($options['LatencyRouting'])) {
            $conn = $conn->withLatencyAwareRouting( $options['LatencyRouting'] );
        }

        // Add ProtocolVersion
        if(isset($options['ProtocolVersion'])) {
            $conn = $conn->withProtocolVersion( $options['ProtocolVersion'] );
        }

        // Add withIOThreads
        if(isset($options['WithIOThreads'])) {
            $conn = $conn->withIOThreads( $options['WithIOThreads'] );
        }

        // Add withConnectionsPerHost
        if(isset($options['ConnPerHost'])) {
            $conn = $conn->withConnectionsPerHost( $options['ConnPerHost']['MinConn'], $options['ConnPerHost']['MaxConn'] );
        }
        
        // Add withTCPNodelay
        if(isset($options['TCPNodelay'])) {
            $conn = $conn->withTCPNodelay( $options['TCPNodelay'] );
        }
        
        // enable keepalive with a 10 second interval
        if(isset($options['TCPKeepalive'])) {
            $conn = $conn->withTCPKeepalive( $options['TCPKeepalive'] );
        }

        // Cassandra’s built-in password authentication
        if(isset($options['Auth'])) {
            $conn = $conn->withCredentials( $optionsAuth['username'], $options['Auth']['password'] );
        }

        // Enabling SSL encryption
        if(isset($options['SSLEncrypt'])) {
            $ssl     = Cassandra::ssl();
            
            // With withTrustedCerts
            if(isset($options['SSLEncrypt']['TrustedCerts'])) {
                $ssl = $ssl->withVerifyFlags( 
                        $options['SSLEncrypt']['TrustedCerts']['node1'], 
                        $options['SSLEncrypt']['TrustedCerts']['node2']
                    );
            }
            
            // With ClientCert
            if(isset($options['SSLEncrypt']['ClientCert'])) {
                $ssl = $ssl->withClientCert( $options['SSLEncrypt']['ClientCert'] );                
            }
            
            // With PrivateKey
            if(isset($options['SSLEncrypt']['PrivateKey'])) {
                $ssl = $ssl->withClientCert( 
                        $options['SSLEncrypt']['PrivateKey']['KeyId'], 
                        $options['SSLEncrypt']['PrivateKey']['KeyPass'] 
                    );                
            }
            
            $conn = $conn->withSSL($ssl);
        }
        
        $this->cluster = $conn->build();
    }
    
    /**
     * Execute Query
     * @param type $Statement
     * @return type
     */
    public function exec( $Statement, $DataType = 'RAW', $OptionalParam = false ) {

        if($DataType == 'RAW')
            $ExeStatement = $this->session->prepare( $Statement );    
        else if($DataType == 'SIMPLE_STATEMENT')
            $ExeStatement = new Cassandra\SimpleStatement( $Statement );
        else
            $ExeStatement = $Statement;

        if(!$OptionalParam)
            return $this->session->execute( $ExeStatement );
        else
            return $this->session->execute( $ExeStatement , $OptionalParam );
    }
}

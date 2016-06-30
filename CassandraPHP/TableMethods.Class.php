<?php

class TableMethods {
    private $CassandraPHP;
    
    public function __construct($cPHP) {
        $this->CassandraPHP = $cPHP;
    }
    
    /**
     * Create table or Columnfamily
     * @param type $Name
     * @param type $Fields
     * @param type $Type
     * @return boolean
     */
    public function Create($Name, $Fields) {
        if(!is_array($Fields))
            return false;
        
        $CreateQuery = "CREATE TABLE ".$Name." ";
        $FieldList = "";
        foreach($Fields as $FieldName=>$FieldType) {
            $FieldList .= ($FieldList<>''?', ':'').$FieldName." ".$FieldType;
        }
        $CreateQuery = $CreateQuery." (".$FieldList.");";
        return $this->CassandraPHP->exec( $CreateQuery, 'SIMPLE_STATEMENT' );
    }
    

    /**
     * Create Index
     * @param type $IndexName
     * @param type $TableName
     * @param type $FieldName
     * @return type
     */
    public function CreateIndex($IndexName, $TableName, $FieldName) {
        $CreateStatement = "CREATE INDEX ".$IndexName." ON ".$TableName." (".$FieldName.");";   
        return $this->CassandraPHP->exec( $CreateStatement, 'SIMPLE_STATEMENT' );
    }
    
    /**
     * Drop Index
     * @param type $IndexName
     * @return type
     */
    public function DropIndex($IndexName) {
        $DropStatement = "DROP INDEX ".$IndexName.";";   
        return $this->CassandraPHP->exec( $DropStatement, 'SIMPLE_STATEMENT' );
    }
    
    /**
     * Drop Table
     * @param type $TableName
     * @return type
     */
    public function DropTable($TableName) {
        $DropStatement = "DROP TABLE ".$TableName.";";   
        return $this->CassandraPHP->exec( $DropStatement, 'SIMPLE_STATEMENT' );        
    }

    /**
     * Insert into table
     * @param type $Table
     * @param type $FieldsWithArgs
     * @return type
     */
    public function Insert($Table, $FieldsWithArgs) {
        if(!is_array($FieldsWithArgs))
            return false;
        
        $Fields = $Args = "";
        foreach($FieldsWithArgs as $Field=>$Arg) {
            $Fields .= ($Fields<>''?', ':'') . $Field;
            
            if(is_array($Arg)) {
                $ArgList = '';
                foreach($Arg as $ListVal) {
                    $ArgList .= ($ArgList<>''?',':'')."'".$ListVal."'";
                }
                $Args .= ($Args<>''?', ':'') . '['.$ArgList.']';
            }
            else 
                $Args .= ($Args<>''?', ':'') . (is_int($Arg) ? $Arg : "'" . $Arg . "'");   
            
        }
        
        $QueryString = 'INSERT INTO ' . $Table . ' ('.$Fields.') VALUES (' . $Args .')';
        
        return $this->CassandraPHP->BatchMode==true ? $QueryString : $this->CassandraPHP->exec( $QueryString );
    }
    
    
    /**
     * Update table
     * @param type $Table
     * @param type $UpdateFieldsWithArgs
     * @param type $FilterFieldsWithArgs
     * @return boolean
     */
    public function Update($Table, $UpdateFieldsWithArgs, $FilterFieldsWithArgs) {
        if(!is_array($UpdateFieldsWithArgs))
            return false;
        
        $Fields = $Args = $UpdateFields = "";
        foreach($UpdateFieldsWithArgs as $Field=>$Arg) {            
            if(is_array($Arg)) {
                $ArgList = '';
                foreach($Arg as $ListVal) {
                    $ArgList .= ($ArgList<>''?',':'')."'".$ListVal."'";
                }
                $Args = '[' . $ArgList . ']';
            }
            else 
                $Args = is_int($Arg) ? $Arg : "'" . $Arg . "'";
            
            if(strpos($Field, ':') !== false) {
                list($Field, $type) = explode(":", $Field);
                
                if($type == 'BEFORE') {
                    $UpdateFields .= ($UpdateFields<>''?', ':'') . $Field.'='.$Args.'+'.$Field;
                }
                else if($type == 'AFTER') {
                    $UpdateFields .= ($UpdateFields<>''?', ':'') . $Field.'='.$Field.'+'.$Args;
                }
                else {
                    $UpdateFields .= ($UpdateFields<>''?', ':'') . $Field.'='.$Args;    
                }
            }
            else
                $UpdateFields .= ($UpdateFields<>''?', ':'') . $Field.'='.$Args;
        }

        $FilterFields = "";
        foreach($FilterFieldsWithArgs as $Field=>$Arg) {
            $FilterFields .= ($FilterFields<>''?' AND ':'') . $Field.'='.(is_int($Arg) ? $Arg : "'" . $Arg . "'");
        }
        
        $QueryString = 'UPDATE ' . $Table . ' SET '.$UpdateFields.' WHERE '.$FilterFields;

        return $this->CassandraPHP->BatchMode==true ? $QueryString : $this->CassandraPHP->exec( $QueryString );
    }
    
    
    /**
     * Select specific data set / table
     * @param type $fields
     * @param type $table
     * @param type $limit
     * @param type $allPages
     * @return boolean
     */
    public function Select($fields, $table, $TableFilters='', $limit = false) {
        
        $QueryString = "SELECT ".$fields." FROM ".$table ;
        
        #Add Where value
        if(is_array($TableFilters) && sizeof($TableFilters) > 0) {
            if($FilterOptions = self::GetFilterQuery($TableFilters)) {
                $QueryString .= " WHERE " . $FilterOptions;
            }
        }
        
        return $this->ExecSelectQuery($QueryString, $limit);
    }
    
    /**
     * Execute Raw Query
     * @param type $QueryString
     * @param type $limit
     * @return boolean
     */
    public function ExecSelectQuery($QueryString, $limit=false) {
        if($limit !== false) {
            $execOptions = new Cassandra\ExecutionOptions(array('page_size' => $limit));
            $execResult = $this->CassandraPHP->exec($QueryString, 'SIMPLE_STATEMENT', $execOptions);
        }
        else 
            $execResult = $this->CassandraPHP->exec($QueryString, 'SIMPLE_STATEMENT');
        
        # Return false if invalid object
        if(!is_object($execResult))
            return false;
        
        $finalResult = array();
        
        # Loop the result
        $i = 0;
        while ($execResult) {
            foreach ($execResult as $row) {
                if(is_array($row)) {
                    foreach($row as $key=>$val) {
                        if(is_object($val))
                            $finalResult[$i][$key] = array(
                                'type' => $val->type()->valueType()->name(),
                                'value' => $val->values()
                            );
                        else
                            $finalResult[$i][$key] = $val;
                    }
                    $i++;
                }
            }
            $execResult = $execResult->nextPage();
        }
        
        //Return final result
        return $finalResult;
    }


    /**
     * Delete from table
     * @param type $Table
     * @param type $FilterFieldsWithArgs
     * @param type $Fields
     * @return type
     */
    public function Delete($Table, $FilterFieldsWithArgs, $Fields=false) {
        
        $FilterFields = "";
        foreach($FilterFieldsWithArgs as $Field=>$Arg) {
            $FilterFields .= ($FilterFields<>''?' AND ':'') . $Field.'='.(is_int($Arg) ? $Arg : "'" . $Arg . "'");
        }
        
        $TargetFields = '';
        if(is_array($Fields)) {
            $TargetFields = implode(",", $Fields);
        }
        
        $QueryString = 'DELETE ' . $TargetFields . ' FROM ' . $Table . ' WHERE '.$FilterFields;
        return $this->CassandraPHP->BatchMode==true ? $QueryString : $this->CassandraPHP->exec( $QueryString );
    }
    
    /**
     * Execute batch statement
     * @param array $Statements
     * @param type $Type
     * @return boolean
     */
    public function Batch(Array $Statements, $Type='BATCH_LOGGED') {
        if(!is_array($Statements))
            return false;
        
        // Instantiate batch
        if($Type == 'BATCH_UNLOGGED')
            $Batch = new Cassandra\BatchStatement(Cassandra::BATCH_UNLOGGED);
        else if($Type == 'BATCH_COUNTER')
            $Batch = new Cassandra\BatchStatement(Cassandra::BATCH_COUNTER);
        else
            $Batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED); 
        
        $this->CassandraPHP->BatchMode = true;
        foreach($Statements as $BatchRow) {
            $QueryString = '';
            // Get Insert statement
            if(isset($BatchRow['INSERT']))
                $QueryString = $this->Insert($BatchRow['INSERT'][0], $BatchRow['INSERT'][1]);

            // Get update statement
            if(isset($BatchRow['UPDATE']))
                $QueryString = $this->Update($BatchRow['UPDATE'][0], $BatchRow['UPDATE'][1], $BatchRow['UPDATE'][2]);

            // Get delete statement
            if(isset($BatchRow['DELETE']))
                $QueryString = $this->Delete($BatchRow['DELETE'][0], $BatchRow['DELETE'][1], $BatchRow['DELETE'][2]);
            
            //Load query on batch Query
            if($QueryString !== false) {
                $BatchStatement = new Cassandra\SimpleStatement( $QueryString );
                $Batch->add( $BatchStatement );
            }
        }
        $this->CassandraPHP->BatchMode = false;
        return $this->CassandraPHP->exec( $Batch, 'READY' );
    }
    
    /**
     * Create View
     * @param type $ViewName
     * @param type $TableName
     * @param type $Fields
     * @param type $TableFilters
     * @param type $PrimaryKey
     * @param type $WithValue
     * @param type $OrderBy
     * @param type $OrderType
     * @return type
     */
    public function CreateView($ViewName, $TableName, $Fields, $TableFilters, $PrimaryKey, $WithValue='', $OrderBy='', $OrderType='ASC') {
        
        $SelectFields = is_array($Fields) && sizeof($Fields) > 0 ? implode(",", $Fields) : "*";      
        
        #Create Initial Query structure
        $CreateQuery = "CREATE MATERIALIZED VIEW ".$ViewName." AS "
                . "SELECT ".$SelectFields." FROM ".$TableName." ";
        
        #Add Where value
        if(is_array($TableFilters) && sizeof($TableFilters) > 0) {
            if($FilterOptions = self::GetFilterQuery($TableFilters)) {
                $CreateQuery .= " WHERE " . $FilterOptions;
            }
        }
        
        #Add Primary Field
        if(is_array($PrimaryKey) && sizeof($PrimaryKey) > 0) {
            $CreateQuery .= "PRIMARY KEY (".implode(",", $PrimaryKey).") ";
        }
        else if($PrimaryKey<>'') {
            $CreateQuery .= "PRIMARY KEY (".$PrimaryKey.") ";
        }
        
        # Add with value
        if($WithValue<>'')
            $CreateQuery .= "WITH ".$WithValue." ";
        
        # Add order By
        if($OrderBy<>'')
            $CreateQuery .= "ORDER BY (".$OrderBy." ".$OrderType.")";
        
        return $this->CassandraPHP->exec( $CreateQuery, 'SIMPLE_STATEMENT' );
    }
    
    /**
     * Create filter query from table
     * @param type $Fields
     * @return boolean|string
     */
    public static function GetFilterQuery($Fields) {
        if(!is_array($Fields))
            return false;
        
        $FilterString = "";
        foreach($Fields as $key=>$val) {
            $FieldValue = isset($val[2]) ? $val[2] : '';
            $FieldValue = is_int($FieldValue) ? $FieldValue : ($FieldValue<>'' ? "'".$FieldValue."'" : '');
            
            $RowString = ($FilterString<>''? (isset($val[3]) ? " ".$val[3] : 'AND' ) : "");
            $RowString .= " " .$val[0] ." ".$val[1]." ".(strlen($val[1]) > 3 ? '' : $FieldValue);
            $FilterString .= $RowString;
        }

        return $FilterString;
    }
    
}
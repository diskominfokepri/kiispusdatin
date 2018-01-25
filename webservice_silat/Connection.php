<?php

/*******************************
 Contoh pendefinisian :
 
 define( 'DB_HOST', 'localhost' ); // set database host
 define( 'DB_USER', 'root' ); // set database user
 define( 'DB_PASS', 'root' ); // set database password
 define( 'DB_NAME', 'yourdatabasename' ); // set database name
 require_once( 'Connection.php' );
 //Initiate the class
 $database = new Connection();
 //OR...
 $database = Connection::getInstance();  
********************************/

Class Connection
{
	private $koneksi = null;
	public $filter;
    static $inst = null;
	public static $counter = 0;


    public function log_db_errors( $error, $query )
    {
        $message = '<p>Error at '. date('Y-m-d H:i:s').':</p>';
        $message .= '<p>Query: '. htmlentities( $query ).'<br />';
        $message .= 'Error: ' . $error;
        $message .= '</p>';
        if( defined( 'SEND_ERRORS_TO' ) )
        {
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'To: Admin <'.SEND_ERRORS_TO.'>' . "\r\n";
            $headers .= 'From: Yoursite <system@'.$_SERVER['SERVER_NAME'].'.com>' . "\r\n";
            mail( SEND_ERRORS_TO, 'Database Error', $message, $headers );   
        }
        else
        {
            trigger_error( $message );
        }
        if( !defined( 'DISPLAY_DEBUG' ) || ( defined( 'DISPLAY_DEBUG' ) && DISPLAY_DEBUG ) )
        {
            echo $message;   
        }
    }

    public function __construct()
    {
    	mb_internal_encoding("UTF-8");
    	mb_regex_encoding( 'UTF-8' );
        mysqli_report( MYSQLI_REPORT_STRICT );
        try {
            $this->koneksi = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
            $this->koneksi->set_charset( "utf8" );
        } catch ( Exception $e ) {
            die( 'Gagal koneksi ke database!' );
        }
    }

    public function __destruct()
    {
        if( $this->koneksi)
        {
            $this->disconnect();
        }
    }

    public function filter( $data )
     {
         if( !is_array( $data ) )
         {
             $data = $this->koneksi->real_escape_string( $data );
             $data = trim( htmlentities( $data, ENT_QUOTES, 'UTF-8', false ) );
         }
         else
         {
             //Self call function to sanitize array data
             $data = array_map( array( $this, 'filter' ), $data );
         }
         return $data;
     }
    

    public function escape( $data )
	{
		if( !is_array( $data ) )
		{
			$data = $this->koneksi->real_escape_string( $data );
		}
		else
		{
			//Self call function to sanitize array data
			$data = array_map( array( $this, 'escape' ), $data );
		}
		return $data;
	}

	public function clean( $data )
	{
		$data = stripslashes( $data );
		$data = html_entity_decode( $data, ENT_QUOTES, 'UTF-8' );
		$data = nl2br( $data );
		$data = urldecode( $data );
		return $data;
	}

	public function db_common( $value = '' )
    {
        if( is_array( $value ) )
        {
            foreach( $value as $v )
            {
                if( preg_match( '/AES_DECRYPT/i', $v ) || preg_match( '/AES_ENCRYPT/i', $v ) || preg_match( '/now()/i', $v ) )
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            if( preg_match( '/AES_DECRYPT/i', $value ) || preg_match( '/AES_ENCRYPT/i', $value ) || preg_match( '/now()/i', $value ) )
            {
                return true;
            }
        }
    }

    public function query( $query )
    {
        $full_query = $this->koneksi->query( $query );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $query );
            return false; 
        }
        else
        {
            return true;
        }
    }

    public function table_exists( $name )
    {
         self::$counter++;
         $check = $this->koneksi->query( "SELECT 1 FROM $name" );
         if($check !== false)
         {
             if( $check->num_rows > 0 )
             {
                 return true;
             }
             else
             {
                 return false;
             }
         }
         else
         {
             return false;
         }
    }

    public function num_rows( $query )
    {
        self::$counter++;
        $num_rows = $this->koneksi->query( $query );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $query );
            return $this->koneksi->error;
        }
        else
        {
            return $num_rows->num_rows;
        }
    }

    public function exists( $table = '', $check_val = '', $params = array() )
    {
        self::$counter++;
        if( empty($table) || empty($check_val) || empty($params) )
        {
            return false;
        }
        $check = array();
        foreach( $params as $field => $value )
        {
            if( !empty( $field ) && !empty( $value ) )
            {
                //Check for frequently used mysql commands and prevent encapsulation of them
                if( $this->db_common( $value ) )
                {
                    $check[] = "$field = $value";   
                }
                else
                {
                    $check[] = "$field = '$value'";   
                }
            }
        }
        $check = implode(' AND ', $check);
        $rs_check = "SELECT $check_val FROM ".$table." WHERE $check";
        $number = $this->num_rows( $rs_check );
        if( $number === 0 )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    public function get_row( $query, $object = false )
    {
        self::$counter++;
        $row = $this->koneksi->query( $query );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $query );
            return false;
        }
        else
        {
            $r = ( !$object ) ? $row->fetch_row() : $row->fetch_object();
            return $r;   
        }
    }

    public function get_results( $query, $object = false )
    {
        self::$counter++;
        //Overwrite the $row var to null
        $row = null;
        
        $results = $this->koneksi->query( $query );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $query );
            return false;
        }
        else
        {
            $row = array();
            while( $r = ( !$object ) ? $results->fetch_assoc() : $results->fetch_object() )
            {
                $row[] = $r;
            }
            return $row;   
        }
    }

    public function insert( $table, $variables = array() )
    {
        self::$counter++;
        //Make sure the array isn't empty
        if( empty( $variables ) )
        {
            return false;
        }
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $field;
            $values[] = "'".$value."'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';
        
        $sql .= $fields .' VALUES '. $values;
        $query = $this->koneksi->query( $sql );
        
        if( $this->koneksi->error )
        {
            //return false; 
            $this->log_db_errors( $this->koneksi->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }

    public function insert_safe( $table, $variables = array() )
    {
        self::$counter++;
        //Make sure the array isn't empty
        if( empty( $variables ) )
        {
            return false;
        }
        
        $sql = "INSERT INTO ". $table;
        $fields = array();
        $values = array();
        foreach( $variables as $field => $value )
        {
            $fields[] = $this->filter( $field );
            //Check for frequently used mysql commands and prevent encapsulation of them
            $values[] = $value; 
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '('. implode(', ', $values) .')';
        
        $sql .= $fields .' VALUES '. $values;
        $query = $this->koneksi->query( $sql );
        
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }

    public function insert_multi( $table, $columns = array(), $records = array() )
    {
        self::$counter++;
        //Make sure the arrays aren't empty
        if( empty( $columns ) || empty( $records ) )
        {
            return false;
        }
        //Count the number of fields to ensure insertion statements do not exceed the same num
        $number_columns = count( $columns );
        //Start a counter for the rows
        $added = 0;
        //Start the query
        $sql = "INSERT INTO ". $table;
        $fields = array();
        //Loop through the columns for insertion preparation
        foreach( $columns as $field )
        {
            $fields[] = '`'.$field.'`';
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        //Loop through the records to insert
        $values = array();
        foreach( $records as $record )
        {
            //Only add a record if the values match the number of columns
            if( count( $record ) == $number_columns )
            {
                $values[] = '(\''. implode( '\', \'', array_values( $record ) ) .'\')';
                $added++;
            }
        }
        $values = implode( ', ', $values );
        $sql .= $fields .' VALUES '. $values;
        $query = $this->koneksi->query( $sql );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $sql );
            return false;
        }
        else
        {
            return $added;
        }
    }

    public function update( $table, $variables = array(), $where = array(), $limit = '' )
    {
        self::$counter++;
        //Make sure the required data is passed before continuing
        //This does not include the $where variable as (though infrequently)
        //queries are designated to update entire tables
        if( empty( $variables ) )
        {
            return false;
        }
        $sql = "UPDATE ". $table ." SET ";
        foreach( $variables as $field => $value )
        {
            
            $updates[] = "`$field` = '$value'";
        }
        $sql .= implode(', ', $updates);
        
        //Add the $where clauses as needed
        if( !empty( $where ) )
        {
            foreach( $where as $field => $value )
            {
                $value = $value;
                $clause[] = "$field = '$value'";
            }
            $sql .= ' WHERE '. implode(' AND ', $clause);   
        }
        
        if( !empty( $limit ) )
        {
            $sql .= ' LIMIT '. $limit;
        }
        $query = $this->koneksi->query( $sql );
        if( $this->koneksi->error )
        {
            $this->log_db_errors( $this->koneksi->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }

    public function delete( $table, $where = array(), $limit = '' )
    {
        self::$counter++;
        //Delete clauses require a where param, otherwise use "truncate"
        if( empty( $where ) )
        {
            return false;
        }
        
        $sql = "DELETE FROM ". $table;
        foreach( $where as $field => $value )
        {
            $value = $value;
            $clause[] = "$field = '$value'";
        }
        $sql .= " WHERE ". implode(' AND ', $clause);
        
        if( !empty( $limit ) )
        {
            $sql .= " LIMIT ". $limit;
        }
            
        $query = $this->koneksi->query( $sql );
        if( $this->koneksi->error )
        {
            //return false; //
            $this->log_db_errors( $this->koneksi->error, $sql );
            return false;
        }
        else
        {
            return true;
        }
    }

    public function lastid()
    {
        self::$counter++;
        return $this->koneksi->insert_id;
    }

    public function affected()
    {
        return $this->koneksi->affected_rows;
    }

    public function num_fields( $query )
    {
        self::$counter++;
        $query = $this->koneksi->query( $query );
        $fields = $query->field_count;
        return $fields;
    }

    public function list_fields( $query )
    {
        self::$counter++;
        $query = $this->koneksi->query( $query );
        $listed_fields = $query->fetch_fields();
        return $listed_fields;
    }

    public function truncate( $tables = array() )
    {
        if( !empty( $tables ) )
        {
            $truncated = 0;
            foreach( $tables as $table )
            {
                $truncate = "TRUNCATE TABLE `".trim($table)."`";
                $this->koneksi->query( $truncate );
                if( !$this->koneksi->error )
                {
                    $truncated++;
                    self::$counter++;
                }
            }
            return $truncated;
        }
    }

    public function display( $variable, $echo = true )
    {
        $out = '';
        if( !is_array( $variable ) )
        {
            $out .= $variable;
        }
        else
        {
            $out .= '<pre>';
            $out .= print_r( $variable, TRUE );
            $out .= '</pre>';
        }
        if( $echo === true )
        {
            echo $out;
        }
        else
        {
            return $out;
        }
    }

    public function total_queries()
    {
        return self::$counter;
    }

    static function getInstance()
    {
        if( self::$inst == null )
        {
            self::$inst = new Connection();
        }
        return self::$inst;
    }

    public function disconnect()
    {
        $this->koneksi->close();
    }

}

?>
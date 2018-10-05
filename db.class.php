<?php

// Title     	: Database CRUD functions.
// File        : db.class.php
// Author    	: M. Langham
// Date      	: 2018/01/20
// Version   	: 1.0.0
// Purpose   	: Class of database CRUD functions.


	class DB {

        /* sensitive user data for accessing a database */
		private $dbHost 	= "???";    // db host IP address
		private $dbUser 	= "???";    // db user name
		private $dbPass  	= "???";    // db user password
		private $dbName		= "???";    // db name


		/* Create and check db connection on each query */
		public function __construct() {

            $conn = new mysqli(
            	$this->dbHost,
            	$this->dbUser,
            	$this->dbPass,
            	$this->dbName
            );

            if ($conn->connect_error) { 
            	die("database connection failure: ".$conn->connect_error); 
            }
            else {
            	$this->db = $conn;
            }

        } /* END DB __construct */



        /* *** Create ***
			Create a new entry in a database table
			@input  $table (string) name of the table
			@input  $data  (array)  data for upload

			@output $create (string) returns id of the newly created DB entry on success, 
                                     or the error if failed.
    	*/
        public function create($table, $data) {

        	// check array contains data and is an array
        	if(!empty($data) && is_array($data)) {
            	$columns = '';		// init and reset
            	$values  = '';		// init and reset

                $data['ts'] = date("Y-m-d H:i:s");

            	// loop to convert data array to CSV string for use in SQL statement
            	$i = 0;
            	foreach($data as $key => $val) {
                	$pre = ($i > 0) ? ', ' : '';		// prepend comma after first iteration
                	$columns .= $pre.$key;
                	$values  .= $pre."'".$val."'";
                	$i++;
            	}

            	// assemble SQL statement
            	$sql_create = "INSERT INTO ".$table." (".$columns.") VALUES (".$values.")";

            	$create = $this->db->query($sql_create);

                return $create ? $this->db->insert_id : $this->db->error;
        	}

            // return an error if the sent data is not an array or is empty
        	else {
                return "FAILURE in DB - >create - data is empty or not an array.";
        	}

        } /* END function DB -> create */



    	/* *** Read ***
    		Read an entry of a database table
    		@input  $table      (string) name of the table
    		@input  $conditions (array)  parameters for data read / return
    			columns (string): Desired columns of data seperated by ','. Return ALL if not set.
                                    To return unique values prefix with DISTINCT. (ex. 'DISTINCT cat0')
    			filters (string): Column compared to value using operators (ex. Country='Mexico' or CustomerID>1)
    			sort    (string): Sort return data by columns, optional ascending (ASC) or descending (DESC), 
    							  multiple allowed. (ex. Country ASC, CustomerName DESC)
    			limit      (int): Max number of rows to return
    			type    (string): count, single, all

			@output $data (array) containing entries matching query, or false if non found.

			syntax)  DB -> read($table, array(columns, filters, sort, limit, type))
			example) DB -> read('inventory');                 returns all entries
			example) DB -> read('users', Country='Mexico');   returns entries where column Country = Mexico
    	*/
        public function read($table, $conditions = array()) {

        	// initialize and reset SQL query string
        	$sql = 'SELECT ';

        	// column selection 
        	$sql .= array_key_exists("columns", $conditions) ? $conditions['columns'] : '*';

        	// table selection
        	$sql .= ' FROM '.$table;

        	// filters
        	if(array_key_exists("filters", $conditions)) {
            	$sql .= ' WHERE ';

            	// loop to append filters
            	$i = 0;
            	foreach($conditions['filters'] as $key => $value) {
                	$pre = ($i > 0) ? ' AND ' : '';
                	$sql .= $pre.$key." = '".$value."'";
                	$i++;
            	}
        	}

        	// sort order
			if(array_key_exists("order_by", $conditions)) {
            	$sql .= ' ORDER BY '.$conditions['order_by']; 
        	}
        
        	// limit
        	if(array_key_exists("limit", $conditions)) {
            	$sql .= ' LIMIT '.$conditions['limit'];
        	}
            
        	// submit the SQL read query 
        	$result = $this->db->query($sql) or die("DB ERROR: ".$this->db->error);

        	// type = count or single
        	if(array_key_exists("type", $conditions) && $conditions['type'] != 'all') {
            	switch($conditions['type']) {
               		case 'count':		// return the number of results matching query
                    	$data = $result->num_rows;
                    	break;
                	case 'single':		// return a single row associative array
                    	$data = $result->fetch_assoc();
                    	break;
                	default:
                    	$data = '';
            	}
        	}

        	// type = all
        	else {
            	if($result->num_rows > 0) {
            		// loop to assemble multiple row associative array 
                	while($row = $result->fetch_assoc()) {
                    	$data[] = $row;
                	}
            	}
        	}

        	// return the data
        	return !empty($data) ? $data : false;

        } /* END function DB -> read */



    	/* *** Update ***
            Modify data in a database table
			@input  $table      (string) name of the table
			@input  $data       (array)  data for upload
            @input  $conditions (array)  parameters for data read / return

			@output $update     (string) returns the id of sucessfully modified rows, 
                                         or the error if failed.
    	*/
        public function update($table, $data, $conditions) {

        	// check array contains data and is an array
			if(!empty($data) && is_array($data)) {
            	
            	$sql_update = '';		
            	$key_val = '';			// will hold a key='value' for SQL statement	
            	$i = 0;

            	foreach($data as $key => $val) {
                	$pre = ($i > 0) ? ', ' : '';
                	$key_val .= $pre.$key."='".$val."'";
                	$i++;
            	}

            	if(!empty($conditions) && is_array($conditions)) {
                	$sql_update .= ' WHERE ';
                	$i = 0;
                	foreach($conditions as $key => $value) {
                    	$pre = ($i > 0) ? ' AND ' : '';
                    	$sql_update .= $pre.$key." = '".$value."'";
                    	$i++;
                	}
            	}
            	$query = "UPDATE ".$table." SET ".$key_val.$sql_update;
            	$update = $this->db->query($query);
            	return $update ? $this->db->affected_rows : $this->db->error;
        	}

        	else {
            	return false;
        	}

        } /* END function DB -> update */



    	/* *** Delete ***
    		Delete an entry from a database table
			@input $table      (string) name of the table
			@input $conditions (array)

			@output $delete (bool) returns 'true' upon sucessful deletion, or 'false' if failed to delete entry
    	*/
        public function delete($table, $conditions) {

        	$sql_delete = '';
        	$i = 0;

        	if(!empty($conditions) && is_array($conditions)) {
            	$sql_delete .= ' WHERE ';
            	
            	foreach($conditions as $key => $value){
                	$pre = ($i > 0) ? ' AND ' : '';
                	$sql_delete .= $pre.$key." = '".$value."'";
                	$i++;
            	}
        	}

        	$query = "DELETE FROM ".$table.$sql_delete;
        	$delete = $this->db->query($query);
        	return $delete ? true : false;

        } /* END function DB -> delete */


	} /* END class DB */

?>

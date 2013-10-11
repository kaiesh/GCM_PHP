<?php
require_once("DatabaseConn.php");
require_once("DatabaseObject.php");
class Core{

	private $dbConnections; //array of database connections
	private $dbConnDefaultKey; //key to default database connection
	private $dbQueryCount; //array that counts how many times each connection is hit
	private $debugset; //Flag to control debugger

	const ERRORLEVEL_LOW = 0;
	const ERRORLEVEL_MED = 1;
	const ERRORLEVEL_HIGH = 2;
	const ERRORLEVEL_CRITICAL = 3;

	function Core(){
		$this->debugger = false;
		session_start();
	}

	/**
	 * Configure the debugger
	 **/
	function debugger($setting){
		$this->debugset = $setting;
		$this->debug("--------Debugger activated-------");
	}

	function get_debugger(){
		return $this->debugset;
	}

	/**
	 * Perform debug action
	 **/
	function debug($msg, $newLine=true){
		//Check if a newline needs to be added to the end of the message
		if ($newLine){
			$msg = $msg."\n";
		}
		//If just set to true, then this will only print to screen
		if ($this->debugset===true){
			$print = true;
		}else if (strlen($this->debugset)>2){
			//must be a filename
			$f = fopen($this->debugset,'a'); //open file for appending
			if ($f){			
				fwrite($f, "[".date("Y-m-d H:i:s")." - Debug] ".$msg);
				fclose($f);
			}else{
				//Could not open file, print to screen
				$print = true;
				$msg = "{!FileIO} ".$msg;
			}
		}
		//Print to screen if appropriate
		if ($print){
			echo "[DEBUG] ".$msg."<br/>";
		}
	}

	function get_db($key=null){
		if ($key==null){
			$key=$this->dbConnDefaultKey;
		}
		return $this->dbConnections[$key];
	}

	/**
	 * Create and store a connection to a specified database, under a key.
	 * It allows for multiple database connections to be configured and for
	 * a default to be set. The first connection established is always set 
	 * to default, until overridden by another setting.
	 **/
	function connect_db(DatabaseConn $dbconn, $connKey=null, $default=null){
		//If a connection key is not provided, make a random one
		if ($connKey==null)
			$connKey = $this->random_string(5,0);
		//If there are no previous connections, create a placeholder for them
		if ($this->dbConnections==null)
			$this->dbConnections = array();
		//Establish the connection
		$mysqli = new mysqli($dbconn->get("server"), $dbconn->get("username"), $dbconn->get("password"), $dbconn->get("database"));
		if ($mysqli->connect_errno){
			//Connection failure
			throw new Exception("Failed to connect to MySQL: ".$mysqli->connect_error);
		}
		//Store the connection resource
		$this->dbConnections[$connKey] = $mysqli;
		//If there are no other default keys, or this is meant to be the default, store the reference
		if (($this->dbConnDefaultKey==null)||$default)
			$this->dbConnDefaultKey = $connKey;
		//If no connections have been establish before, prepare the count array
		if ($this->dbQueryCount==null)
			$this->dbQueryCount = array();
		//Prepare the count variable for this specific connection
		$this->dbQueryCount[$connKey] = 0;
		//Inform the calling function as to what key the connection is under
		return $connKey;
	}

	/**
	 * Return list of keys to active db connections
	 **/
	function get_db_keys(){
		return array_keys($this->dbConnections);
	}

	/**
	 * Execute db query on specified (or default) database conection and return the resource
	 **/
	function db($query, $key=null){
		if ($this->dbConnDefaultKey==null)
			throw new Exception("No database connection configured");

		if ($key==null)
			$key = $this->dbConnDefaultKey;

		$this->dbQueryCount[$key]++;
		$this->debug("[SQL] ".$query);
		return $this->dbConnections[$key]->query($query);
	}

	/**
	 * Escape anything as appropriate
	 **/
	function escape($data, $key=null){
		if ($this->dbConnDefaultKey==null)
			throw new Exception ("No database connection configured");
		
		if ($key==null)
			$key = $this->dbConnDefaultKey;

		//some intelligent handling needs to go here
		return $this->escape_string($data, $key);
	}
	/**
	 * Escape a string for DB input, using a specific DB conn (or default)
	 **/
	function escape_string($text, $key=null){
		if ($this->dbConnDefaultKey==null)
			throw new Exception ("No database connection configured");
		if ($key==null)
			$key = $this->dbConnDefaultKey;
		return $this->dbConnections[$key]->real_escape_string($text);

	}

	//Handle ints that overflow intval
	function bigintval($value) {
		$value = trim($value);
		if (ctype_digit($value)) {
			return $value;
		}
		$value = preg_replace("/[^0-9](.*)$/", '', $value);
		if (ctype_digit($value)) {
			return $value;
		}
		return 0;
	}
	

}

?>
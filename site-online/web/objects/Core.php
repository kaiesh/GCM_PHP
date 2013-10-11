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
	 * Session management - allows for users to be set and retrieved
	 **/
	function setSession(User $uObj){
		$_SESSION["user"] = $uObj;
	}

	function getSession(){
		$uObj = $_SESSION["user"];
		if ($uObj)
			$uObj->refresh_core(); //because the old core is serialised!
		return $uObj;
	}

	function endSession(){
		$_SESSION["user"] = null;
		unset($_SESSION["user"]);
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
	 * Return a 2D array from a DB query
	 **/
	function db_array($query, $transpose=false, $assoc=false, $key=null){
		$this->debug("DB Array Called with transpose at [".$transpose."] and assoc at [".$assoc."]");
		if (!$res = $this->db($query, $key))
			return false;
		//PHP only has integer and float numeric types.
		$fieldsinfo = $res->fetch_fields(); //mysqli_fetch_fields($res);
		//print "<br />bldbr fieldsinfo=<pre>"; var_dump($fieldsinfo); print "</pre>";
		foreach ($fieldsinfo as $ix => $fieldinfo) {
			if (in_array($fieldinfo->type, array(1,2,3,8,9,16)))
				$fieldtypemap[$assoc ? $fieldinfo->name : $ix] = "integer";
			else if (in_array($fieldinfo->type, array(4,5,246)))
				$fieldtypemap[$assoc ? $fieldinfo->name : $ix] = "float";
			else if (in_array($fieldinfo->type, array(7)))
				$fieldtypemap[$assoc ? $fieldinfo->name : $ix] = null;
			else
				$fieldtypemap[$assoc ? $fieldinfo->name : $ix] = null;
		}
		//print "<br />bldbr fieldtypemap=<pre>"; var_dump($fieldtypemap); print "</pre>";
		$r = 0;
		$result = array();
		while ($row = ($assoc ? @$res->fetch_assoc() : $res->fetch_row())){ //mysqli_fetch_assoc($res) : @mysqli_fetch_row($res))) {
			//print "<br />bldbr row=<pre>"; var_dump($row); print "</pre>";
			foreach ($row as $k => $v) 
				if ($transpose)
					$result[$k][] = $v === null ? null : ($fieldtypemap[$k] === "integer" ? (integer)$v : ($fieldtypemap[$k] === "float" ? (float)$v : $v));
				else
					$result[$r][$k] = $v === null ? null : ($fieldtypemap[$k] === "integer" ? (integer)$v : ($fieldtypemap[$k] === "float" ? (float)$v : $v));
			$r++;
		}
		$res->free(); //mysqli_free_result($res);
		//print "<hr />sql=$sql<br />"; var_dump($result);
		return $result;
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
		
	/*Random string generator */
	function random_string($charCount, $numCount)
	{
		$character_set_array = array();
		$character_set_array[] = array('count' => $charCount, 'characters' => 'abcdefghijklmnopqrstuvwxyz');
		$character_set_array[] = array('count' => $numCount, 'characters' => '0123456789');
		$temp_array = array();
		foreach ($character_set_array as $character_set) {
			for ($i = 0; $i < $character_set['count']; $i++) {
				$temp_array[] = $character_set['characters'][rand(0, strlen($character_set['characters']) - 1)];
			}
		}
		shuffle($temp_array);
		return implode('', $temp_array);
	}

	/* Array to csv string */
	function array_to_string($arr){
		$str = "";
		foreach ($arr as $item){
			if (strlen(trim($item))>0){
				if ($str!="")
					$str .= ",";
				$str .= $item;
			}
		}
		return $str;
	}

	/* HTML minifier */
	function minify($buffer)
	{
		$search = array(
						'/\>[^\S ]+/s', //strip whitespaces after tags, except space
						'/[^\S ]+\</s', //strip whitespaces before tags, except space
						'/(\s)+/s' // shorten multiple whitespace sequences
						);
		$replace = array(
						 '>',
						 '<',
						 '\\1'
						 );
		$buffer = preg_replace($search, $replace, $buffer);

		return $buffer;
	}
	function critical_error($data){
		//a  method to report critical errors
	}

}

?>
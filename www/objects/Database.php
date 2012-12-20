<?
/****************************************************************************
* Database
*
* See the explanation provided in the Core file.
****************************************************************************/

class Database{
	/***************************
	* The DB object holds and maintains the connection to the
	* production database server, and acts as the middle man for all db
	* queries and executions. This allows for DB query logging/tracking and
	* counting.
	*/

	/* Private variables */
	private $queries = 0;
	private $db_add;
	private $db_user;
	private $db_pass;
	private $db_name;
	private $db_resource;
	private $connected = false;

	function Database(){
	}

	function setDBaddress($content){
		$this->db_add = $content;
	}
	function setDBuser($content){
		$this->db_user = $content;
	}
	function setDBpass($content){
		$this->db_pass = $content;
	}
	function setDBname($content){
		$this->db_name = $content;
	}
	function connectDB(){
		if (!$this->connected){
			$db = mysql_pconnect($this->db_add, $this->db_user, $this->db_pass);
			if (!$db){
				$repErr = true;
				$objErrMsg = "KK Object Err: Failed to establish connection";
				$this->connected = false;
			}else{
				try{
					mysql_select_db($this->db_name);
					$repErr = false;
				}catch (Exception $e){
					$repErr = true;
					$objErr = $e->getMessage();
					$this->connected = false;
				}
			}
			if ($repErr) {
				$alertMsg = "URGENT! URGENT!\n\nA call was made to a webpage, but it could not be serviced because the db did not respond properly. See the connection details as follows:\n\nObject Reported Error: ".$objErr."\nReferred From: ".$_SERVER['HTTP_REFERER']."\nServer Name: ".$_SERVER['SERVER_NAME']."\nRequested URL: ".$_SERVER['REQUEST_URI']."\nRequest method: ".$_SERVER['REQUEST_METHOD']."\nUser Agent: ".$_SERVER['HTTP_USER_AGENT']."\nRemote Address: ".$_SERVER['REMOTE_ADDR']."\n\nDB Connection Details\n-------------\nDB Address: ".$this->db_add."\nDB User: ".$this->db_user."\n\nInvestigate immediately!!\n";
				//This is the only time the PHP mail function is used without going through a mail object!
				mail("support@kamkash.com", "URGENT: DB Connection Failed", $alertMsg, "From: KK DB Obj <support@kamkash.com>\r\nCC: Kaiesh <kaiesh@kaiesh.com>");
				throw new Exception("Error: The database is currently inaccessible. This error has been reported to the support team. Please try again later.");
			}else{
				$this->db_resource = $db;
				$this->connected = true;
				return true;
			}
		}
	}

	function execute($command){
		$this->queries++;
		return mysql_query($command);
	}

	function lastInsertID($linkIdentifier = null){
		//cheat here
		return mysql_insert_id($this->db_resource);
	}

	function getQueryCount(){
		return $this->queries;
	}
}
?>
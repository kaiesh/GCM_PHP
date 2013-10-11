<?
/****************************************************************************
* DatabaseObject v5
*
* Not to be confused with the Database file, this abstract class is designed
* to allow for objects to be defined as unique database rows/records and
* then subsequently manipulated within PHP.
*
* This class takes the overhead of retrieving all fields from the database
* row, and providing support to get, set, and save that data, while protecting
* the primary key.
*
* V2 of the object accepts arrays as primary keys. It is backward
* compatible with v1 that takes primitives
*
* V3 of the object allows for a new entry to be added to the DB, but only
* if there is one primary key - v4 will need to allow for multiple primary
* keys when adding.
*
* V4 expects the Core to be defined from globals within the constructor of the
* inheriting class
*
* V5 is not compatible with any previous version. Method signatures have changed
* and a config function is expected to be called to calibrate the object.
* This introduces efficiencies in the object by not requiring the db to be
* hit when the object is loaded just to represent an ID
****************************************************************************/

abstract class DatabaseObject{
	private $dbArr;
	protected $core;
	private $primaryKey;
	private $table;
	private $primaryKeyValue;
	private $loaded = false;


	protected function config($tableName, $primaryKeyName, $primaryKeyValue){
		$this->primaryKey = $primaryKeyName;
		$this->table = $tableName;
		$this->primaryKeyValue = $primaryKeyValue;
	}

	protected function loadData($dbRow=false){
		if ((!$this->primaryKey)||(!$this->table)||(!$this->primaryKeyValue))
			throw new Exception ("Database object not configured - unable to load!");

		if ((is_array($this->primaryKeyName))&&(is_array($this->primaryKeyValue))){
			$whereClause = " WHERE ";
			//assumes arrays are of the same length
			$arrSize = sizeof($this->primaryKeyName);
			for ($i=0; $i< $arrSize; $i++){
				if ($i > 0){
					$whereClause .= " AND";
				}
				$whereClause .= " ".$this->primaryKeyName[$i]."='".$this->primaryKeyValue[$i]."'";
			}
			$whereClause .=";";
		}else{
			$whereClause = " WHERE ".$this->primaryKey."='".$this->primaryKeyValue."';";
		}
		if (!$dbRow){
			$query = "SELECT * FROM ".$this->table." ".$whereClause;
			$res = $this->core->db($query);
			//$this->core->debug("Res [".$res."] size: ".$res->num_rows);
			if ($res->num_rows==1){
				$dbArr = array();
				$dbRow = $res->fetch_object();
			}else{
				throw new Exception ("Unique object with identifier ".$this->primaryKeyValue." not found");
			}
		}
		$varArr = get_object_vars($dbRow);
		foreach ($varArr as $key=>$value){
			//$this->core->getDebugger()->debug("Loading: ".$key." -> ".$value);
			$this->dbArr[$key] = $value;
		}
		$this->loaded = true;
	}

	public function get($key){
		if (!$this->loaded){
			$this->loadData();
		}
		return $this->dbArr[$key];
	}

	protected function set($key, $value){
		if (!$this->loaded){
			$this->loadData();
		}
		if ($key != $this->primaryKey){
			$this->dbArr[$key] = $value;
			return true;
		}else{
			return false;
		}
	}

	protected function save(){
		if (!$this->loaded){
			$this->loadData();
		}
		$count = 0;
		$query = "UPDATE ".$this->table." SET ";
		if (is_array($this->primaryKey)){
			$arrayCheck = true;
		}else{
			$arrayCheck = false;
		}

		foreach ($this->dbArr as $key=>$value){
			if ((($arrayCheck)&&(in_array($key, $this->primaryKey)))||((!$arrayCheck)&&($key == $this->primaryKey))){
				//primary keys can not be changed with this method
			}else{
				if ($count > 0){
					$query .=",";
				}
				$query .= $this->core->escape($key)."='".$this->core->escape($value)."' ";
				$count++;
			}
		}
		$query .= " WHERE ".$this->core->escape($this->primaryKey)."='".$this->core->escape($this->dbArr[$this->primaryKey])."';";
		//echo $query;
		$this->core->db($query);
	}
  protected static function makeNew($tableName, $primaryKey, $assocArr, $objectType){
	  global $core;
	  $core->getDebugger()->debug("DatabaseObject::makeNew called with ".sizeof($assocArr)." elements in the associative array");
	  $keyString = "";
	  $valueString = "";
	  foreach ($assocArr as $key=>$value){
		  if ($keyString!=""){
			  $keyString .=",";
		  }
		  $keyString .= $core->escape($key);
		  
		  if ($valueString!=""){
			  $valueString .= ",";
		  }
		  $valueString.="'".$core->escape($value)."'";
	  }
	  $ins = "INSERT INTO ".$tableName." (".$keyString.") VALUES (".$valueString.");";
	  $res = $core->db($ins);
	  if ($res){
		  $getInsert = "SELECT * FROM ".$tableName." WHERE ".$primaryKey."='".$core->dbLastInsertID()."';";
		  $giRes = $core->db($getInsert);
		  if (($giRes)&&($giRes->num_rows==1)){
			  $giObj = $giRes->fetch_object();
			  $newObj = new $objectType($core, $giObj->$primaryKey, $giObj, true);
			  return $newObj;
		  }else{
			  throw new Exception("Unable to locate newly inserted record");
		  }
	  }else{
		  throw new Exception("Unable to create new entry");
	  }
  }
}

?>

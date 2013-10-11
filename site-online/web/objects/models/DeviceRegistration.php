<?
class DeviceRegistration extends DatabaseObject{
  function DeviceRegistration($id, $data = false, $objectData = false){
	  global $core;
	  $this->core = $core;
	  //Check if the core is available in globals
	  if ($this->core==null)
		  throw new Exception ("Core not available");
	  $this->config("deviceRegistration", "entryID", $id);

	  if (($objectData)&&($objectData->clientID==$id))
		  $this->loadData($objectData);

  }
  
  function getID(){
    return $this->get("entryID");
  }
  function getRegID(){
     return $this->get("registrationID");
  }
  public static function create($registrationID){
   global $core;
   $assocArr = array(
               "registrationID" => $registrationID
             );
   $rObj = parent::makeNew($core, "deviceRegistration", "entryID", $assocArr, "DeviceRegistration");
   return $rObj;
  }
  public static function deleteAll(){
    global $core;
    $cleanQ = "TRUNCATE deviceRegistration;";
    $core->db($cleanQ);
  }
}
?>
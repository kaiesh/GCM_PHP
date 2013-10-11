<?

class DeviceRegistration extends DatabaseObject{
  function DeviceRegistration(&$coreRef, $id, $data = false, $objectData = false){
    $this->loadCore($coreRef);
    if ($objectData){
      $this->loadData("deviceRegistration", "entryID", $data->clientID, $data);
    }else{
      $this->loadData("deviceRegistration", "entryD", $id);
    }
  }
  
  function getID(){
    return $this->get("entryID");
  }
  function getRegID(){
     return $this->get("registrationID");
  }
  public static function create(Core &$core, $registrationID){
   $assocArr = array(
               "registrationID" => $registrationID
             );
   $rObj = parent::makeNew($core, "deviceRegistration", "entryID", $assocArr, "DeviceRegistration");
   return $rObj;
  }
  public static function deleteAll(Core &$core){
    $cleanQ = "TRUNCATE deviceRegistration;";
    $core->db($cleanQ);
  }
}
?>
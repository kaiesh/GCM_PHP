<?
class GCMPush{
  private $core;
  private $apikey;
  private $drObjArr;
  const GCM_URL = 'https://android.googleapis.com/gcm/send';
  
  function GCMPush(Core &$coreRef){
    $this->core=$coreRef;
  }
  
  function setAPIKey($ak){
    $this->apikey = $ak;
  }
  function getAllIDs(){
    if (!$this->drObjArr){
      $this->drObjArr = array();
      $dbQ = "SELECT * FROM deviceRegistration WHERE 1";
      $dbRes = $this->core->db($dbQ);
      if (($dbRes)&&(mysql_num_rows($dbRes) > 0)){
        while ($dbRow = mysql_fetch_object($dbRes)){
          $this->drObjArr[] = new DeviceRegistration($this->core, $dbRow->entryID, $dbRow, true);
        }
      }
    }
    return $this->drObjArr;
  }
  function pushToAll($msgArr){
    if (is_array($msgArr)&&($this->apikey!=null)){
      //Load all registrations from the DB
      $this->getAllIDs();
      //Cycle through the objects to build a registration ID array
      $regArr = array();
      for ($i=0; $i<sizeof($this->drObjArr); $i++){
        $regArr[] = $this->drObjArr[$i]->getRegID();
      }
      return $this->executePush($regArr, $msgArr);
    }else{
      throw new Exception("Invalid message format, or no API key set. Push not triggered.");
    }
  }
  
  function pushToOne(DeviceRegistration $regEntry, $msgArr){
    $arr = array($regEntry->getID());
    return $this->executePush($arr, $msgArr);
  }
  
  private function executePush($regArr, $msgArr){
      //Prepare variables
      $fields = array(
                      'registration_ids'  => $regArr,
                      'data'              => $msgArr,
                      );

      $headers = array( 
                          'Authorization: key=' . $this->apikey,
                          'Content-Type: application/json'
                      );

      // Open connection
      $ch = curl_init();

      // Set the url, number of POST vars, POST data
      curl_setopt( $ch, CURLOPT_URL, GCMPush::GCM_URL );
      curl_setopt( $ch, CURLOPT_POST, true );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );

      // Execute post
      $result = curl_exec($ch);

      // Close connection
      curl_close($ch);

      //Return push response as array
      return json_decode($result);
  }


}

?>
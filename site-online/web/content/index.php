<?
require_once("objects/GCMCore.php");
require_once("objects/models/DeviceRegistration.php");
$core = new GCMCore();

$retArr = array();
if ($_GET["deviceid"]){
  $regObj = DeviceRegistration::create($core, $core->dbReadyTxt($_GET["deviceid"]));
  if ($regObj){
    $retArr["status"] = true;
  }else{
    $retArr["status"]=false;
  }
}else{
  $retArr["status"] =false;
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache");
header('Content-Type: application/json');
echo json_encode($retArr);

?>
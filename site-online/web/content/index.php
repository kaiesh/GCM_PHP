<?
require_once("../objects/Settings.php");
require_once("../objects/Core.php");
require_once("../objects/models/DeviceRegistration.php");
$core = new Core();
global $core;
$core->debugger(Settings::$debug);

$db = new DatabaseConn(Settings::$db_server, Settings::$db_user, Settings::$db_pass, Settings::$db_name);
$db_sets = array("prod"=>$db);
$db_key = "prod";
$core->connect_db($db_sets[$db_key], $db_key, true);
$core->debug("Database Connected");

$retArr = array();
if ($_GET["deviceid"]){
	try{
		$regObj = DeviceRegistration::create($core->escape($_GET["deviceid"]));
	}catch (Exception $e){
		 $retArr["error"] = $e->getMessage();
	}
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
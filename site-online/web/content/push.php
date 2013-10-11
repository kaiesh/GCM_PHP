<html>
<head>
  <title>GCM Demo</title>
</head>
<body>
<?
require_once("../objects/Core.php");
require_once("../objects/models/DeviceRegistration.php");
require_once("../objects/controllers/GCMPush.php");
require_once("../objects/Settings.php");

$core = new Core();
global $core;
$core->debugger(Settings::$debug);

$db = new DatabaseConn(Settings::$db_server, Settings::$db_user, Settings::$db_pass, Settings::$db_name);
$db_sets = array("prod"=>$db);
$db_key = "prod";
$core->connect_db($db_sets[$db_key], $db_key, true);
$core->debug("Database Connected");


$gcm = new GCMPush();
$gcm->setAPIKey(Settings::$gcm_key);

if ($_GET["action"] == "delete"){
  DeviceRegistration::deleteAll();
}  

$count = sizeof($gcm->getAllIDs());
?>
<h1>There are <? echo $count; ?> IDs registered in the database</h1>
<? if ($count>0){ ?>
  <form method="get">
  <h3>Please enter the message you want to push...</h3>
  <input type="text" name="pushmsg"><input type="submit" value="Push!">
  <input type="hidden" name="action" value="push">
  </form>
  <hr>
  <?

  if ($_GET['action']=="push"){
    $msg = array ( "message" => $_GET["pushmsg"] );
    $resp = $gcm->pushToAll($msg);
    $count = sizeof($gcm->getAllIDs());
    ?>
  <h2>Push triggered to <? echo $count; ?> IDs in the DB...</h1>
  <h3>Server said:</h3>
    <? print_r($resp);

  }
  ?>
  <hr>
  <h3><form method="get"><input type="hidden" name="action" value="delete"><input type="submit" value="Click here to delete all IDs from the DB"></form></h3>
  <?
}

?>
  
</body>
</html>
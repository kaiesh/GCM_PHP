<html>
<head>
  <title>GCM Demo</title>
</head>
<body>
<?
require_once("objects/GCMCore.php");
require_once("objects/models/DeviceRegistration.php");
require_once("objects/controllers/GCMPush.php");

$core = new GCMCore();
$gcm = new GCMPush($core);
$gcm->setAPIKey("[[ PUT YOUR API KEY HERE ]]");

if ($_GET["action"] == "delete"){
  DeviceRegistration::deleteAll($core);
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
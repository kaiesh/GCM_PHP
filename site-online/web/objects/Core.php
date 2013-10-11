<?
/****************************************************************************
* Core
*
* The core object, along with the Database, Debugger and kkMailMessage objects
* are intended to provide a PHP framework that enables rapid deployment of
* projects while providing most standard functionality through easily
* extendable methods. The Core is an abstract class that requires to be
* extended by a custom class based on the requirements of the project.
*
* The Debugger and Database objects provide for a comprehensive set of audit
* and management tools to enable ease of integration with MySQL and on-screen
* feedback. These objects do not need to be edited or extended unless very
* specific functionality is required.
*
* This abstract class should not be edited directly. Only extended.
****************************************************************************/
require_once("Database.php");
require_once("Debugger.php");
require_once("kkMailMessage.php");
abstract class Core{

  /* The abstracted database connector must be careful not to repeat the
   * connection everytime it is called - it should detect if a connection
   * is already in place, and simply continue if so
   */
  private $dbconn;
  private $debugger;
  private $supportEmail = "your-support@email.com";
  private $YOUR_ERROR_REPORT_EMAIL = "your-error@email.com";
  private $birthtime;
  private $dispEngine;
  private $objectStore;
  private $objectPath;

  /* This function has to be implemented in the actual functional core,
   * as the DB details will change every time.
   */
  abstract function connectDB();

  /* Store the creation time when the core is made */
  function __construct() {
    $this->birthtime = Core::microtime_float();
    $objectStore = array();
  }
  function timeAlive(){
    return Core::microtime_float() - $this->birthtime;
  }
  /* When the actual core is fired up, this element should be configured
   * else, all error messages will be sent to the kamkash account
   */
  function setSupportEmail($content){
    $this->supportEmail = $content;
  }
  function getSupportEmail(){
    return $this->supportEmail;
  }
  function setObjectPath($content){
    $this->objectPath = $content;
  }
  function getObjectPath(){
    return $this->objectPath;
  }
  function getDebugger($writefile = false){
    if (!$this->debugger){
      if ($writefile){
        $debug = new Debugger($this->getObjectPath());
      }else{
        $debug = new Debugger(false);
      }
      $this->debugger = $debug;
    }
    return $this->debugger;
  }
  /* The display engine allows rendering to screen, and rendering of mail
   * messages into strings.
   */
  function getDisplayEngine(){
    return $this->get("DisplayEngine", true);
  }
  /* This is a generic GET command - any object that exists, can be
   * retrieved using this method - if the object does not exist, then
   * an exception is thrown
   */
  function get($element, $coreReqd){
    if (!$this->objectStore[$element]){
      if (file_exists($this->getObjectPath()."/".$element.".php")){
        require_once($element.".php");
        if ($coreReqd){
          $this->objectStore[$element] = new $element($this);
        }else{
          $this->objectStore[$element] = new $element();
        }
      }else{
        throw new Exception ("Object ".$this->getObjectPath()."/".$element." can not be found!");
      }
    }
    return $this->objectStore[$element];
  }
  /* Error Reporting */
  function reportError($errSubject, $errorMsg){
    $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP']; //$_SERVER['HTTP_X_FORWARDED_FOR'] for SSL

    $URL = "http://" . getenv("HTTP_HOST") . ereg_replace( "[^!#-9:;=?-Z_a-z~]", "",  substr(getenv("REQUEST_URI"), 0, 200) );
    $sessionSHA = sha1(session_id());
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $serverTime = date('l jS \of F Y h:i:s A');
    $techInfo = "\n--------\nIP: $ip\nURL: $URL\nSession SHA: $sessionSHA\nUser Agent String: $userAgent\nServer Timestamp: $serverTime\n---- END OF REPORT ----";
    $errMsg = new kkMailMessage($this);
    $errMsg->setSubject("Error: ".$errSubject);
    $errMsg->setFrom("Error Report <".$YOUR_ERROR_REPORT_EMAIL.">");
    $errMsg->setTo($YOUR_ERROR_REPORT_EMAIL);
    $errMsg->setTextBody("---- REPORT STARTS ----\n".$errorMsg.$techInfo);
    $errMsg->setMsgType("Error Report");
    $errMsg->makeMessageReady();
    $errMsg->sendMessage();
  }

  /* Provide database protection function */
  function dbReadyTxt($txt){
    $this->connectDB();
    $inArr = array("\'","\"", "'", "\\");
    $repArr = array("&#39;","&#34;", "&#39;", "&#92;");
    return mysql_real_escape_string(str_replace($inArr, $repArr, $txt));
  }
  /* Completely hide the database by abstracting the connection establish */
  function establishDB($db_add, $db_user, $db_pass, $db_name){
    if (!$this->dbconn){
      $db = new Database();
      $db->setDBaddress($db_add);
      $db->setDBuser($db_user);
      $db->setDBpass($db_pass);
      $db->setDBname($db_name);
      $db->connectDB();
      $this->dbconn = $db;
    }
    return true;
  }
  /* Execute the database command */
  function db($query){
    $this->connectDB();
    $this->getDebugger()->debug("[SQL: ".$query."] ");
    return $this->dbconn->execute($query);
  }
  /* Provide access to specific DB functions */
  function dbLastInsertID($linkIdentifier = null){
    return $this->dbconn->lastInsertID($linkIdentifier);
  }
    function isMobile(){
        $useragent=$_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
            return true;
        }else{
            return false;
        }
    }

  /* Return how many db queries have been run so far */
  function getQueryCount(){
    $this->connectDB();
    return $this->dbconn->getQueryCount();
  }

  /** Microtime **/
  public static function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }

  /**
  Validate an email address.
  Provide email address (raw input)
  Returns true if the email address has the email
  address format and the domain exists.
  */
  function validEmail($email)
  {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex)
    {
    $isValid = false;
    }
    else
    {
    $domain = substr($email, $atIndex+1);
    $local = substr($email, 0, $atIndex);
    $localLen = strlen($local);
    $domainLen = strlen($domain);
    if ($localLen < 1 || $localLen > 64)
    {
      // local part length exceeded
      $isValid = false;
    }
    else if ($domainLen < 1 || $domainLen > 255)
    {
      // domain part length exceeded
      $isValid = false;
    }
    else if ($local[0] == '.' || $local[$localLen-1] == '.')
    {
      // local part starts or ends with '.'
      $isValid = false;
    }
    else if (preg_match('/\\.\\./', $local))
    {
      // local part has two consecutive dots
      $isValid = false;
    }
    else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
    {
      // character not valid in domain part
      $isValid = false;
    }
    else if (preg_match('/\\.\\./', $domain))
    {
      // domain part has two consecutive dots
      $isValid = false;
    }
    else if
    (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
    {
      // character not valid in local part unless
      // local part is quoted
      if (!preg_match('/^"(\\\\"|[^"])+"$/',
      str_replace("\\\\","",$local)))
      {
      $isValid = false;
      }
    }
    if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
    {
      // domain not found in DNS
      $isValid = false;
    }
    }
    return $isValid;
  }
}
?>
<?
/****************************************************************************
* kkMailMessage
*
* See the explanation in the Core object.
****************************************************************************/

class kkMailMessage{
  /*********************************
  * kkMailMessage - version 0.1
  * The vmMailMessage class is a full implementation of a mail message
  * and manages all necessary actions related to managing an email
  * message.
  **********************************/
  //Create private variables
  private $to;
  private $from;
  private $subject;
  private $textBody;
  private $htmlBody;
  private $msgHeader;
  private $fullMessage;
  private $type;
  private $cc;

  private $uniqueRef;

  private $ready;
  private $sentStatus;
  private $kkCore;

  private $copyAdmin;

  private $SmtpServer;
  private $PortSMTP;
  private $SmtpUser;
  private $SmtpPass;

  /* Constructor */
  function kkMailMessage(Core &$coreRef){
    $this->kkCore = $coreRef;
    $this->ready = false;
    $this->sentStatus = false;
    $this->copyAdmin = false;
    $this->kkCore->getDebugger()->debug("New Mail Message instantiated");
  }

  /* Accessor methods */
  private function resetStatus(){
    //Whenever a change is done, the status must be reset
    $this->sentStatus = false;
    $this->ready = false;
  }
  function setTo($content){
    $this->resetStatus();
    $this->to = $content;
  }
  function setFrom($content){
    $this->resetStatus();
    $this->from = $content;
  }
  function setSubject($content){
    $this->resetStatus();
    $this->subject = $content;
  }
  function setTextBody($content){
    $this->resetStatus();
    $this->textBody = $content;
  }
  function setHTMLBody($content){
    $this->resetStatus();
    $this->htmlBody = $content;
  }
  function setMsgType($content){
    $this->resetStatus();
    $this->type = $content;
  }
  function setCopyAdmin($bool){
    $this->copyAdmin = $bool;
  }
  function setCC($emailAddress){
    $this->resetStatus();
    $this->cc = $emailAddress;
    //$this->cc = "spammy@kaiesh.com";
  }
  function setSMTPServer($user, $pass, $server, $port=25){
    $this->SmtpServer = $SmtpServer;
    $this->SmtpUser = base64_encode ($SmtpUser);
    $this->SmtpPass = base64_encode ($SmtpPass);
    $this->PortSmtp = $port;
  }
  function removeSMTP(){
    $this->SmtpServer = null;
    $this->SmtpUser = null;
    $this->SmtpPass = null;
    $this->PortSmtp = null;
  }

  function getTo(){
    return $this->to;
  }
  function getFrom(){
    return $this->from;
  }
  function getCC(){
    return $this->cc;
  }
  function getSubject(){
    return $this->subject;
  }
  function getHeader(){
    return $this->msgHeader;
  }
  function getMessage(){
    return $this->fullMessage;
  }
  function getMsgType(){
    return $this->type;
  }
  function getSentStatus(){
    return $this->sentStatus;
  }
  function getReadyStatus(){
    return $this->ready;
  }
  function getUniqueRef(){
    return $this->uniqueRef;
  }


  /* Object functionality */
  function makeMessageReady(){
    /* This will in effect compile the message so that it can be
    sent. If there is a HTML component it will prepare the headers
    accordingly, otherwise it will simply prep a standard text
    message
    */
    if (strlen($this->type) < 1){
      $this->ready = false;
    }else{
      //make a unique reference, seed it with as much random content as possible
      $this->uniqueRef = sha1(time().rand(0,5000).$this->to);

      if (strlen($this->htmlBody)>0) {
        /* this means there is a HTML component that needs to be
        integrated */
        $this->ready = $this->makeCompositeMessage();
      }else{
        //text only email
        $this->msgHeader = "From: ".$this->from."\r\nReply-To: ".$this->from;
        $this->fullMessage = $this->textBody;
        $this->ready = true;
      }
    }
    return $this->ready;
  }

  private function makeCompositeMessage(){
    /* This should only be called when the HTML component of a msg
    * exists. This will prep all variables so that when sending is
    * invoked, it is sent properly.
    */
    // Create a boundary string. It must be unique
    // So we use the MD5 algorithm to generate a random hash
    $random_hash = md5(date('r', time()));
    $headers = "From: ".$this->from."\r\nReply-To: ".$this->from;
    // Add boundary string and mime type specification (multipart bc. we're using plain & html)
    $headers .= "\r\nContent-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"";
  /* ** DO NOT CHANGE INDENTING BELOW ** */
// (b) Text Header
$msgbody = '
--PHP-alt-'.$random_hash.'
Content-Type: text/plain; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit
';
// (c) Text Body Itself
$msgbody .= $this->textBody;
// (d) HTML Header
$msgbody .='
--PHP-alt-'. $random_hash.'
Content-Type: text/html; charset="iso-8859-1"
Content-Transfer-Encoding: 7bit
';
// (e) HTML Body Itself
$msgbody .= "<html><body>".$this->htmlBody."<a href='http://www.venuemirror.com'><img src='http://www.venuemirror.com/venuemirror.php?r=".$this->uniqueRef."' alt='Powered by VenueMirror' align='right' border='0'></a></body></html>";
// (f) Message Tail
$msgbody .= '
--PHP-alt-'. $random_hash.'--';

    // Load the compiled variables into the object variables
    $this->msgHeader = $headers;
    $this->fullMessage = $msgbody;
    return true;
  }

  function sendMessage(){
    $this->kkCore->getDebugger()->debug("Trigger mail send - ready status: ".$this->ready);
    if ($this->ready){
      if ($this->copyAdmin){
        //Admin is meant to be copied on this message, so mark CC
        $this->msgHeader .= "\r\nCC: ".$this->kkCore->getSupportEmail();
      }
      if ($this->cc){
        //Copy the specified address as well
        $this->msgHeader .="\r\nCC: ".$this->cc;
      }
      //Prepare SQL statement for logging this message
      $logMsg = "INSERT INTO maillog (emailTo, emailFrom, subject, msgType, body, shaRef) VALUES('".$this->kkCore->dbReadyTxt($this->to)."', '".$this->kkCore->dbReadyTxt($this->from)."', '".$this->kkCore->dbReadyTxt($this->subject)."', '".$this->kkCore->dbReadyTxt($this->type)."', '".$this->kkCore->dbReadyTxt($this->msgHeader)."\n\n".$this->kkCore->dbReadyTxt($this->fullMessage)."', '".$this->uniqueRef."');";
      //Use the core db function to write to the database
      @$lmResult = $this->kkCore->db($logMsg);
      //the message can't simply be sent again, it needs to be recompiled!
      $this->ready = false;

      if ($this->SmtpServer){
        return $this->SendMail();
      }else{
        return mail($this->to, $this->subject, $this->fullMessage, $this->msgHeader);
      }
    }else{
      return false;
    }
  }


  private function SendMail(){
    if ($SMTPIN = fsockopen ($this->SmtpServer, $this->PortSMTP)){
      fputs ($SMTPIN, "EHLO ".$HTTP_HOST."\r\n");
      $talk["hello"] = fgets ( $SMTPIN, 1024 );
      fputs($SMTPIN, "auth login\r\n");
      $talk["res"]=fgets($SMTPIN,1024);
      fputs($SMTPIN, $this->SmtpUser."\r\n");
      $talk["user"]=fgets($SMTPIN,1024);
      fputs($SMTPIN, $this->SmtpPass."\r\n");
      $talk["pass"]=fgets($SMTPIN,256);
      fputs ($SMTPIN, "MAIL FROM: <".$this->from.">\r\n");
      $talk["From"] = fgets ( $SMTPIN, 1024 );
      fputs ($SMTPIN, "RCPT TO: <".$this->to.">\r\n");
      $talk["To"] = fgets ($SMTPIN, 1024);
      fputs($SMTPIN, "DATA\r\n");
      $talk["data"]=fgets( $SMTPIN,1024 );
      fputs($SMTPIN, "To: <".$this->to.">\r\n".$this->messageHeader."\r\nSubject:".$this->subject."\r\n\r\n\r\n".$this->fullMessage."\r\n.\r\n");
      $talk["send"]=fgets($SMTPIN,256);
      //CLOSE CONNECTION AND EXIT ...
      fputs ($SMTPIN, "QUIT\r\n");
      fclose($SMTPIN);
      //
    }
    return $talk;
  }
}

?>
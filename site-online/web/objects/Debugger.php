<?
/****************************************************************************
* Debugger
*
* See the explanation in the Core file.
****************************************************************************/

class Debugger{
	/*********************************
	* Debugger - version 0.2
	* The Debugger is a simple object, designed to allow tracing of
	* code errors, and can be flipped on or off within the software itself.
	* By default, the debugger will be in OFF mode, and a function call
	* must be made to turn it on.
	*
	* The debugger assumes that the directory below it called debug-output
	* has a file in it called "debug.txt" that it has write access to.
	**********************************/

	/* Variable declarations */
	/* Class Constants */
	//no constants

	/* Working Variables */
	private $debuggerOn = false;
	private $debugFile;
	private $fileHandler;
	private $fileOutput;

	function Debugger($writeFile){
		if ($writeFile){
			$debugFile = $writeFile."/debug-output/debug.txt";
			$this->fileOutput = true;
		}else{
			$this->fileOutput = false;
		}
		if ($this->fileOutput){
			//Open the file handler
			$this->fileHandler = fopen($this->debugFile, 'a'); //Open debug file in append mode
		}
	}
	function __destruct() {
		if ($this->fileOutput){
			fclose($this->fileHandler);
		}
	}

	function debug($msg, $newLine = true){
		if ($this->debuggerOn){
			if ($this->fileOutput){
				fwrite($this->fileHandler, "[Debug (".date("D d M y - H:i:s:u")."): ".$msg."]\n");
			}else{
				echo "[Debug: ".$msg."]";
				if ($newLine){
					echo "<br/>\n";
				}
			}
		}
	}

	function turnOn(){
		$this->debuggerOn = true;
		$this->debug("Debugger On");
	}
	function turnOff(){
		$this->debug("Deactiving Debugger");
		$this->debuggerOn = false;
	}
}
?>
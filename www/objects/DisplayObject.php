<?
class DisplayObject{
	private $dispItems;
	private $templateName;

	function DisplayObject(){
		$this->dispItems = array();
	}

	function setTemplate($templateName){
		$this->templateName = $templateName;
	}

	function getTemplate(){
		return $this->templateName;
	}

	function set($param, $value){
		$this->dispItems[$param] = $value;
	}

	private function obRender($dispObj){
		ob_start();
		eval ('include "'.$dispObj->getTemplate().'";');
		$visualCode = ob_get_contents();
		ob_end_clean();
		return $visualCode;
	}

	function render($param){
		if ($param instanceof DisplayObject){
			return $this->obRender($param);
		}else if ($this->dispItems[$param] instanceof DisplayObject){
			return $this->obRender($this->dispItems[$param]);
		}else if ($this->dispItems[$param] != null){
			//should just be a string
			return $this->dispItems[$param];
		}
	}
}
?>
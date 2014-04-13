<?php
class Zend_View_Helper_Shoutform {
	function shoutform() {
		if (Zend_Auth::getInstance()->hasIdentity())
		{
			$identity = Zend_Auth::getInstance()->getIdentity();
			if ($identity['rank'] > 0)
			{
				echo <<<EOHTML
<form id=shoutform method=post>
<input type=text name=shout />
</form>
EOHTML;
			}
		}
	}
}
?>
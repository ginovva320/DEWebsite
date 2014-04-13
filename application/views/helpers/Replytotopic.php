<?php
class Zend_View_Helper_Replytotopic extends Zend_View_Helper_Abstract {
	function replytotopic($topicId, $type = 'topic', $public = null, $suffix = '') {
		$url = $this->view->baseUrl() . '/post/add/type/'. $type .'/id/' . $topicId;
		if ($public != null) {
			$url .= '/public/' . $public;
		}

		if(Zend_Auth::getInstance()->hasIdentity())	{
			$id = Zend_Auth::getInstance()->getIdentity();
			return <<<EOHTML
<section class=post id=replysection$suffix style=display:none; >
	<header>
		<h2>Reply</h2>
	</header>
	<article>
		<form id=replypost$suffix action={$url} method=post>
			<textarea id="myMessage$suffix" name="message" class="required" title="Message" rows="6" style=width:640px; ></textarea>
			<div class=de_button>Preview<input id=preview$suffix type=checkbox checked /></div>
			<input class=de_button type=submit value=Post />
		</form>
	</article>
	<h2>Post Preview</h2>
	<article id=previewpost$suffix>
	</article>
</section>
EOHTML;
		} else {
			return <<<EOHTML
<section class=post id=replysection style=display:none; >
	<header>
		<div>Reply</div>
	</header>
	<article>
		<form id=replypost>
			<textarea id="myMessage" name="message" class="required" title="Message" rows="6" style=width:770px; disabled=true>You must login to reply...</textarea>
			<div class=de_button>Preview<input id=preview type=checkbox checked /></div>
			<input class=de_button type=submit value=Post disabled=true />
		</form>
	</article>
	<h2>Post Preview</h2>
	<article id=previewpost>
	</article>
</section>
EOHTML;
		}
	}
	
	public $view;
	
	public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
    }
    
    public function direct()
    {
    	
    }
}
?>
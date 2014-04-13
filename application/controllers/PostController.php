<?php

class PostController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
	}
	
	public function indexAction()
	{
		$this->_redirect("/forum");
	}

	public function viewAction()
	{
		$this->_redirect("/forum");
	}

	public function deleteAction()
	{
		if (!$this->_auth->hasIdentity())
		{
			$this->_redirect('/');
		}
		
		if ($this->getRequest()->isPost()) {
			$deletionText = "<p><em><strong>This post has been deleted.</strong></em></p>";
			
			$this->_helper->layout->disableLayout();
			
			$identity = $this->_auth->getIdentity();
				
			$id = $this->getRequest()->id;
			
			mysqli_query($this->_dbc,
				"UPDATE posts
				SET text='$deletionText', deleted=1
				WHERE id=$id AND account_id=$identity[id]");

			echo $deletionText;
		}
	}

	public function editAction()
	{
		// action body
	}

	public function addAction()
	{
		if (!$this->_auth->hasIdentity())
		{
			$this->_redirect('/');
		}
		
		if ($this->getRequest()->isPost()) {	
			$identity = $this->_auth->getIdentity();
				
			$id = $this->getRequest()->id;
			$type = $this->getRequest()->type;
	
			$p = $this->getRequest()->getPost();
			// $text = auto_link_text($this->paragraphSplit(strip_tags($p['message'])));
			$text = mysqli_real_escape_string($this->_dbc,$p['message']);
			
			if ($type == 'topic')
			{
				$result = mysqli_multi_query($this->_dbc,
					"INSERT INTO posts
					(text, account_id, time, topic_id)
					VALUES ('$text', $identity[id], UNIX_TIMESTAMP(), $id);
					UPDATE topics
					SET time=UNIX_TIMESTAMP()
					WHERE id=$id;
					SELECT COUNT(*) AS count
					FROM posts
					WHERE topic_id = $id"
				);
				
				mysqli_next_result($this->_dbc);
				mysqli_next_result($this->_dbc);
				
				$result = mysqli_fetch_array(mysqli_use_result($this->_dbc));
				$count = $result['count'];
				
				$this->view->post = array('text' => $text, 'time' => new DateTime(), 'count' => $count);
				$this->view->identity = $identity;
				$this->_helper->layout->disableLayout();
			}
			else if ($type == 'application')
			{
				$public = $this->getRequest()->public;
				$result = mysqli_multi_query($this->_dbc,
					"INSERT INTO posts
					(text, account_id, time, application_id, public)
					VALUES ('$text', $identity[id], UNIX_TIMESTAMP(), $id, $public);
					SELECT COUNT(*) AS count
					FROM posts
					WHERE application_id = $id"
				);
				
				mysqli_next_result($this->_dbc);
				
				$result = mysqli_fetch_array(mysqli_use_result($this->_dbc));
				$count = $result['count'];
				
				if ($public == 'true') {
					$result = mysqli_query($this->_dbc,"SELECT COUNT(*) AS count FROM applications WHERE id = $id AND account_id = $identity[id]");
					$data = mysqli_fetch_array($result);
					if ($data['count'] == 0) {
						$result = mysqli_query($this->_dbc,"SELECT a.email FROM applications ap JOIN accounts a ON ap.account_id = a.id WHERE ap.id = $id");
						$data = mysqli_fetch_array($result);
						$url = $_SERVER['SERVER_NAME'] . $this->view->url(array('controller' => 'application', 'action' => 'view', 'id' => $id), null, true);
						$mail = new Zend_Mail();
						$mail->setBodyHtml(<<<EOHTML
<p>
	A new comment has been posted on your guild application. Click on the link below to view your application.
</p>
<p>
	<a href="$url">$url</a>
</p>
EOHTML
						);
						$mail->setFrom('admin@dishonorelite.com','Dishonor Elite');
						$mail->addTo($data['email']);
						$mail->setSubject('New Application Comments');
						$mail->send();
					}
				}
				
				$this->view->post = array('text' => $text, 'time' => new DateTime(), 'count' => $count);
				$this->view->identity = $identity;
				$this->_helper->layout->disableLayout();
			}
		}
	}

	function paragraphSplit($text)
	{
		$text = preg_replace("/(\r\n|\n\r)/", "\n", $text);
		$text = preg_replace("/\n{2,}/", "</p><p>", $text);
		$text = preg_replace("/\n/", "<br/>\n", $text);
		$text = preg_replace("/<\/p><p>/", "</p>\n<p>", $text);
		return "<p>$text</p>";
	}
}

/**
 * Replace links in text with html links
 *
 * @param  string $text
 * @return string
 */
function auto_link_text($text)
{
   $pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
   $callback = create_function('$matches', '
       $url       = array_shift($matches);
       $url_parts = parse_url($url);

       $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
       $text = preg_replace("/^www./", "", $text);

       $last = -(strlen(strrchr($text, "/"))) + 1;
       if ($last < 0) {
           $text = substr($text, 0, $last) . "&hellip;";
       }

       return sprintf(\'<a rel="nofollow" target="_blank" href="%s">%s</a>\', $url, $text);
   ');

   return preg_replace_callback($pattern, $callback, $text);
}


<?php

class TopicController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
		if ($this->_auth->hasIdentity())
		{
			$this->identity = $this->_auth->getIdentity();
			$this->rank = $this->identity['rank'];
		}
		else {
			$this->identity = null;
			$this->rank = -1;
		}
	}

//	public function indexAction()
//	{
//		// action body
//	}

	public function viewAction()
	{
		$id = $this->getRequest()->id;
		$this->view->post = $this->getRequest()->post ? $this->getRequest()->post : 0;
		
		$this->view->identity = $this->identity;
		
		$result = mysqli_query($this->_dbc,
			"SELECT t.id, t.title, b.title AS b_title, b.id AS b_id, f.title AS f_title, f.id AS f_id, f.visibility, t.locked, t.sort
			FROM topics t
			JOIN boards b
			ON t.board_id = b.id
			JOIN forums f
			ON b.forum_id = f.id
			WHERE t.id = $id"
		);
		
		$this->view->topic = mysqli_fetch_array($result);
		
		if ($this->view->topic['visibility'] > $this->rank) {
			$this->_redirect("/forum");
		}
		
		$this->view->posts = mysqli_query($this->_dbc,
			"SELECT p.time, p.id, p.text, p.deleted, a.id AS a_id, c.name AS c_name, c.class AS c_class, c.thumb AS c_thumb
			FROM posts p
			JOIN accounts a
			ON a.id = account_id
			JOIN characters c
			ON a.main_id = c.id
			WHERE topic_id = $id
			ORDER BY time ASC"
		);
		
		mysqli_query($this->_dbc,
			"UPDATE topics
			SET views = views + 1
			WHERE id = $id"
		);
	}

	public function deleteAction()
	{
		// action body
	}

	public function addAction()
	{
		if ($this->getRequest()->isPost()) {
			$id = $this->getRequest()->id;
			$identity = $this->_auth->getIdentity();
			
			$this->_helper->layout->disableLayout();
			
			$p = $this->getRequest()->getPost();
			$title = mysqli_real_escape_string($this->_dbc,$p['title']);
			//$text = auto_link_text($this->paragraphSplit(strip_tags($p['message'])));
			$text = mysqli_real_escape_string($this->_dbc,$p['message']);
			
			mysqli_query($this->_dbc,
				"INSERT INTO topics
				(time, title, account_id, board_id)
				VALUES (UNIX_TIMESTAMP(), '$title', {$identity['id']}, $id)");
	
			$topicId = mysqli_insert_id($this->_dbc);
			mysqli_query($this->_dbc,
				"INSERT INTO posts
				(text, account_id, time, topic_id)
				VALUES ('$text', {$identity['id']}, UNIX_TIMESTAMP(), $topicId)");
			
			$this->view->identity = $identity;
			$this->view->topicId = $topicId;
			$this->view->topicTitle = $p['title'];
		}
	}

	public function editAction()
	{
		// action body
	}
	
	public function lockAction()
	{
		if ($this->rank < 30 || $this->getRequest()->id == null || ($this->getRequest()->lock != "0" && $this->getRequest()->lock != "1"))
				$this->_redirect("/forum");
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$query = "UPDATE topics
				  SET locked = {$this->getRequest()->lock}
				  WHERE id = {$this->getRequest()->id}";
		
		mysqli_query($this->_dbc,$query);
		$this->_redirect("/topic/view/id/{$this->getRequest()->id}");
	}
	
	public function stickyAction()
	{
		if ($this->rank < 30 || $this->getRequest()->id == null || ($this->getRequest()->sort != "0" && $this->getRequest()->sort != "10"))
				$this->_redirect("/forum");
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$query = "UPDATE topics
				  SET sort = {$this->getRequest()->sort}
				  WHERE id = {$this->getRequest()->id}";
		
		mysqli_query($this->_dbc,$query);
		$this->_redirect("/topic/view/id/{$this->getRequest()->id}");
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


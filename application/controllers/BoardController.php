<?php

class BoardController extends Zend_Controller_Action
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

	public function indexAction()
	{
		$this->_redirect("/forum");
	}

	public function viewAction()
	{
		$id = $this->getRequest()->id;
		
		$this->view->identity = $this->identity;
		$result = mysqli_query($this->_dbc,
			"SELECT b.title, b.id, f.visibility
			 FROM boards b
			 JOIN forums f
			 ON b.forum_id = f.id
			 WHERE b.id = $id"
		);
		
		$this->view->board = mysqli_fetch_array($result);
		$visibility = $this->view->board['visibility'];
		
		if ($visibility > $this->rank) {
			$this->_redirect('/forum');
		}
		
		$this->view->topics = mysqli_query($this->_dbc,
			"SELECT t.id, t.title, t.sort, t.views, t.locked,
			(SELECT COUNT(*) FROM posts WHERE topic_id = t.id)-1 AS replies,
			(SELECT c.name FROM posts p JOIN accounts a ON p.account_id = a.id JOIN characters c ON a.main_id = c.id WHERE p.topic_id = t.id ORDER BY p.time DESC LIMIT 1) AS poster_name,
			(SELECT c.class FROM posts p JOIN accounts a ON p.account_id = a.id JOIN characters c ON a.main_id = c.id WHERE p.topic_id = t.id ORDER BY p.time DESC LIMIT 1) AS poster_class,
			t.time AS poster_time
			FROM topics t
			JOIN boards b
			ON t.board_id = b.id
			WHERE t.board_id = $id
			ORDER BY sort DESC, t.time DESC"
		);
	}

	public function deleteAction()
	{
		// action body
	}

	public function addAction()
	{
		// action body
	}

	public function editAction()
	{
		// action body
	}


}










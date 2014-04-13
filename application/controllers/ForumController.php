<?php

class ForumController extends Zend_Controller_Action
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
			$this->rank = -1;
		}
    }

	public function indexAction()
	{
		$this->view->forumId = $this->getRequest()->id;
		$this->view->forums = mysqli_query($this->_dbc,
			"SELECT f.id AS fid, f.title AS ftitle, b.id AS bid, b.title AS btitle, b.description, (SELECT COUNT(*) FROM topics t WHERE t.board_id = b.id) AS num
			FROM boards b
			JOIN forums f
			ON b.forum_id = f.id
			WHERE f.visibility <= $this->rank
			ORDER BY f.sort"
		);
	}

}


<?php

class ShoutController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
	}

	public function indexAction()
	{
		if ($this->_auth->hasIdentity())
		{
			$id = $this->_auth->getIdentity();
			$post = $this->getRequest()->getPost();

			mysqli_query($this->_dbc,
				"INSERT INTO shouts
				(time, text, account_id)
				VALUES (UNIX_TIMESTAMP(), '$post[shout]', $id[id])");
			
			$shout = array(
				'text' => $post['shout'],
				'time' => time(),
				'class' => $id['class'],
				'name' => $id['name']
			);
				
			echo $this->view->partial('partials/shout.phtml', array('shout' => $shout));
		}
	}
}
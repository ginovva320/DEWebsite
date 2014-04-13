<?php

class IndexController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_armory = $registry->armory;
	}

	public function indexAction()
	{
		$stream = @fopen(APPLICATION_PATH . '/log/log.txt', 'a', false);
		if (! $stream) {
			throw new Exception('Failed to open stream');
		}

		$writer = new Zend_Log_Writer_Stream($stream);
		$logger = new Zend_Log($writer);

		$logger->info('Informational message');
	}

	public function mumbleAction()
	{
		// action body
	}

	public function addonsAction()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->view->addons = mysqli_query($this->_dbc,
			"SELECT * FROM addons");
	}

	public function incompatibleAction()
	{
		$this->_helper->layout->disableLayout();
	}
}
<?php

class CharacterController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
	}

	public function indexAction()
	{
	}

	public function viewAction()
	{
		// action body
	}

	public function addAction()
	{
		// action body
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






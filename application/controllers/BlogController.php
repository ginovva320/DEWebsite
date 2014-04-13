<?php

class BlogController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
		if ($this->_auth->hasIdentity())
		{
			$this->identity = $this->_auth->getIdentity();
			$this->rank = $identity['rank'];
		}
		else
			$this->rank = -1;
	}

	public function indexAction()
	{
		$this->view->blogs = $this->_em->createQuery(
			'SELECT b
			FROM Application_Model_Blog b 
			WHERE b.visibility <= ?1
			ORDER BY b.sort
			')
		->setParameter(1,$this->rank)
		->getResult();
		
		$raids = $this->_em->createQuery(
			'SELECT r
			FROM Application_Model_Raid r 
			ORDER BY r.tier DESC
			')
		->getResult();

		foreach ($raids as $raid) {
			$normalDefeated = 0;
			foreach ($raid['normal_mode']['bosses'] as $boss) {
				if ($boss['defeated']) {
					$normalDefeated++;
				}
			}
			$normalDefeated = floor($normalDefeated/count($raid['normal_mode']['bosses']) * 100);
			$heroicDefeated = 0;
			foreach ($raid['heroic_mode']['bosses'] as $boss) {
				if ($boss['defeated']) {
					$heroicDefeated++;
				}
			}
			$heroicDefeated = floor($heroicDefeated/count($raid['heroic_mode']['bosses']) * 100);
			echo <<<EOHTML
<img src= /><p>{$raid['name']}<br/>Normal Mode: {$normalDefeated}%<br/>Heroic Mode: {$heroicDefeated}%</p>
EOHTML;
		}
	}

	public function viewAction()
	{
		// action body
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










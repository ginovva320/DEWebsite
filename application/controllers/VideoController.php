<?php

class VideoController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
	}

	public function indexAction()
	{

	}

	public function youtubeAction()
	{
		$youtube = new Zend_Gdata_YouTube();
		$this->view->feed = $youtube->getUserUploads('DishonorElite');
	}

	public function youtubeViewAction()
	{
		$id = $this->getRequest()->id;
		
		if ($id == null)
			$this->_redirect('/video/youtube');
			
		$youtube = new Zend_Gdata_YouTube();
		$this->view->vid = $youtube->getVideoEntry($id);
	}

	public function raidviewAction()
	{
		$query = "SELECT * FROM raidviews ORDER BY id DESC";
		$this->view->raidviews = mysqli_query($this->_dbc,$query);
	}

	public function raidviewSetupAction()
	{
		$id = $this->getRequest()->id;
		
		if ($id == null)
			$this->_redirect('/video/raidview');
			
		$this->_helper->layout->disableLayout();
		$query = <<<EOSQL
			SELECT v.id, v.role, v.mumble, c.name, c.class 
			FROM raidview_videos v 
			JOIN accounts a
			ON v.account_id = a.id
			JOIN characters c
			ON a.main_id = c.id
			WHERE v.raidview_id = $id
EOSQL;
		$this->view->videos = mysqli_query($this->_dbc,$query);
		
		$query = <<<EOSQL
			SELECT a.id, a.title 
			FROM raidview_tracks a 
			WHERE a.raidview_id = $id
EOSQL;
		$this->view->tracks = mysqli_query($this->_dbc,$query);
		$this->view->id = $id;
	}

	public function raidviewWatchAction()
	{
		$videos = $this->getRequest()->videos;
		$track = $this->getRequest()->track;
		$id = $this->getRequest()->id;
		
		if ($id == null || $videos == null || $track == null)
			$this->_redirect('/video/raidview');
		
		if ($track == '0')
			$this->view->mumble = true;
		else
			$this->view->track = $track;
		
		$where = "";
		foreach ($videos as $video)
		{
			$where .= "v.id = $video OR ";
		}
		$where = substr($where,0,-4);
		
		$query = <<<EOSQL
		SELECT v.id, v.role, v.mumble, c.name, c.class 
			FROM raidview_videos v 
			JOIN accounts a
			ON v.account_id = a.id
			JOIN characters c
			ON a.main_id = c.id
			WHERE $where
EOSQL;

		$this->view->videos = mysqli_query($this->_dbc,$query);
		$this->view->id = $id;
	}
}

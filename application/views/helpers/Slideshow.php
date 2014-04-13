<?php
class Zend_View_Helper_Slideshow extends Zend_View_Helper_Abstract {
	function slideshow() {
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity())
		{
			$identity = $auth->getIdentity();
			$rank = $identity['rank'];
		}
		else
			$rank = -1;
			
		$registry = Zend_Registry::getInstance();
		$dbc = $registry->dbc;
		$slides = mysqli_query($dbc,
				"SELECT s.title, s.caption, s.link, a.id
				FROM slides s
				JOIN attachments a
				ON s.attachment_id = a.id
				WHERE s.visibility <= $rank
				ORDER BY s.sort ASC");
		
		echo '<section id=slideshow-container class=lift>';
		while ($slide = mysqli_fetch_array($slides))
		{
			$path = $this->view->baseUrl() . '/img/slideshow/slide_' . $slide['id'] . '.png';
			
			if ($slide['link'])
				echo '<a href=' . $slide['link'] . '>' . '<img src='. $path . ' alt="' . $slide['title']. '::' . $slide['caption'] . '" /></a>';
			else
				echo '<img src='. $path . ' alt="' . $slide['title']. '::' . $slide['caption'] . '" />'; 
		}
		echo '</section>';
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
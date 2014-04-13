<?php

class ApplicationController extends Zend_Controller_Action
{

    public function init()
    {
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
    }

    public function indexAction()
    {
		if ($this->_auth->hasIdentity()) {
			$identity = $this->_auth->getIdentity();
			$this->view->rank = $identity['rank'];
			
			if ($identity['rank'] >= 20) {
				$this->view->applications = mysqli_query($this->_dbc,
					"SELECT app.status, app.time, app.id, c.name, c.class,
					(SELECT COUNT(*) FROM posts p WHERE p.application_id = app.id) as comments
					FROM applications app
					JOIN accounts a
					ON a.id = app.account_id
					JOIN characters c
					ON c.id = a.main_id
					ORDER BY time DESC"
				);
			} else {
				$result = mysqli_query($this->_dbc,
					"SELECT id FROM applications WHERE account_id = $identity[id]"
				);
				$this->view->application = mysqli_fetch_array($result);
			}
		} else {
			$this->view->rank = -1;
		}
    }

    public function addAction()
    {
    	if (!$this->_auth->hasIdentity()) {
    		$this->_redirect('/application');
    	}
    	
    	$this->view->identity = $this->_auth->getIdentity();	
        
		$result = mysqli_query($this->_dbc,
			"SELECT id FROM applications WHERE account_id = {$this->view->identity['id']}"
		);
		
		$this->view->application = mysqli_fetch_array($result) ? 'true' : 'false';
		
      	if ($this->getRequest()->isPost()) {
      		$this->_helper->layout->disableLayout();
    		$this->_helper->viewRenderer->setNoRender();
    		
      		$post = $this->getRequest()->getPost();
      		
      		$exp = array();
      		foreach ($post as $key => $val) {
      			if (substr($key, 0, 3) == "exp") {
      				$i = substr($key, 3, 4);
      				$exp["$i"] = $val;
      			}
      		}
      		
      		$about = $this->paragraphSplit($post['about']);
      		$info = $this->paragraphSplit($post['info']);
      		$gear = $this->paragraphSplit($post['gear']);
      		$interface = $this->paragraphSplit($post['interface']);
      		$wol = $this->paragraphSplit($post['wol']);
      		$guilds = $this->paragraphSplit($post['guilds']);
      		$strats = $this->paragraphSplit($post['strats']);
      		$join = $this->paragraphSplit($post['join']);
      		$joke = $this->paragraphSplit($post['joke']);
      		$question = $this->paragraphSplit($post['question']);
      		
      		$query = "INSERT INTO applications
      				(time, account_id, status, about, info, gear, interface, wol, guilds, strats, whyjoin, internet, mic, joke, question)
      				VALUES (UNIX_TIMESTAMP(), {$this->view->identity['id']}, 'Open', '$about', '$info', '$gear', '$interface', '$wol', '$guilds', '$strats', '$join', $post[internet], $post[mic], '$joke', '$question')";
      		
      		mysqli_query($this->_dbc, $query);
      		
      		$appId = mysqli_insert_id($this->_dbc);
      		
      		if (!empty($exp)) {
      			$query = "INSERT INTO experiences (mode_id, status, application_id) VALUES ";
      			foreach ($exp as $key => $val) {
      				$status = $post['exp'.$key];
      				$query .= "($key, '$status', $appId),";
      			}
      			$query = substr($query, 0, -1);
      		}
      		
      		mysqli_query($this->_dbc, $query);
      		
      		$url = $this->view->url(array('controller' => 'application', 'action' => 'view', 'id' => $appId), null, true);
      		echo <<<JS
<script>
window.location = '$url';
</script>
JS;
      	} else {
      		$this->view->raids = mysqli_query($this->_dbc,
				"SELECT r.name AS r_name, m.id AS m_id, m.name AS m_name
				FROM raids r
				JOIN modes m
				ON m.raid_id = r.id 
				WHERE r.active = 1"
			);
      	}
    }

    public function viewAction()
    {
    	if ($this->_auth->hasIdentity()) {
    		$identity = $this->_auth->getIdentity();
    		$rank = $identity['rank'];
    		
	    	$id = $this->getRequest()->id;
	    	
	        $result = mysqli_query($this->_dbc,
	        	"SELECT app.*, c.server, c.name, c.class, a.id AS a_id
	        	FROM applications app
	        	JOIN accounts a
	        	ON app.account_id = a.id
	        	JOIN characters c
	        	ON a.main_id = c.id
	        	WHERE app.id = $id"
	        );
	        
	        $this->view->app = mysqli_fetch_array($result);
	        
	        $this->view->exp = mysqli_query($this->_dbc,
	        	"SELECT m.name AS mode, r.name AS name, e.status AS status
				FROM experiences e
				JOIN modes m
				ON e.mode_id = m.id
				JOIN raids r
				ON m.raid_id = r.id
				WHERE e.application_id = {$this->view->app['id']}"
	        );
	        
	        if ($rank < 11 && ($identity['id'] != $this->view->app['a_id'])) {
	        		$this->_redirect("/application");
	        }
	        
	        $this->view->public = mysqli_query($this->_dbc,
	        	"SELECT p.time, p.text, c.name AS c_name, c.class AS c_class, c.thumb AS c_thumb
				FROM posts p
				JOIN accounts a
				ON a.id = account_id
				JOIN characters c
				ON a.main_id = c.id
				WHERE application_id = $id AND public=TRUE
				ORDER BY time ASC"
			);
			
			if ($rank > 10) {
				$this->view->private = mysqli_query($this->_dbc,
	        	"SELECT p.time, p.text, c.name AS c_name, c.class AS c_class, c.thumb AS c_thumb
				FROM posts p
				JOIN accounts a
				ON a.id = account_id
				JOIN characters c
				ON a.main_id = c.id
				WHERE application_id = $id AND public=FALSE
				ORDER BY time ASC"
			);
			}

	        $this->view->rank = $rank;
    	} else {
    		$this->_redirect("/application");
    	}
    }
    
    public function changeAction() {
    	$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$id = $this->getRequest()->id;
		$a_id = $this->getRequest()->aid;
		$status = $this->getRequest()->status;
		
		if (!$this->_auth->hasIdentity() || $id == null || $status == null || $a_id == null) {
			$this->_redirect("/application");
		}
		
		$identity = $this->_auth->getIdentity();
		
		if ($identity['rank'] < 30) {
			$this->_redirect("/application");
		}
		
		$status = ucwords(strtolower($status));
		$rank = $status == 'Accepted' ? 10 : 0;
		
		mysqli_multi_query($this->_dbc,
			"SELECT a.email
			FROM applications ap 
			JOIN accounts a 
			ON ap.account_id = a.id 
			WHERE ap.id = $id;
			UPDATE applications
			SET status = '$status'
			WHERE id = $id;
			UPDATE accounts
			SET rank = $rank
			WHERE id = $a_id"
		);
		
		$result = mysqli_use_result($this->_dbc);
		$data = mysqli_fetch_array($result);
		
		$mail = new Zend_Mail();
		$url = $_SERVER['SERVER_NAME'] . $this->view->url(array('controller' => 'application', 'action' => 'view', 'id' => $id), null, true);
		$mail->setBodyHtml(<<<EOHTML
<p>
	Your guild application status has changed. Click on the link below to view your application.
</p>
<p>
	<a href="$url">$url</a>
</p>
EOHTML
		);
		$mail->setFrom('admin@dishonorelite.com','Dishonor Elite');
		$mail->addTo($data['email']);
		$mail->setSubject('New Application Status');
		try {
			$mail->send();
		} catch (Zend_Exception $e) {
			echo '<pre>';
			var_dump($result,$query,$e);
			die();
		}
		
		$this->_redirect("/application");
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






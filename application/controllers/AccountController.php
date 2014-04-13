<?php

class AccountController extends Zend_Controller_Action {

  public function init() {
    $registry = Zend_Registry::getInstance();
    $this->_dbc = $registry->dbc;
    $this->_auth = Zend_Auth::getInstance();
    $this->_armory = $registry->armory;
    $this->_armory->characterExcludeFields(array(
				'stats','talents','reputation','titles','professions',
				'appearance','companions','mounts','achievements',
				'progression','pvp','quests','pets'
			));
	$this->_armory->setCharactersCacheTTL(0);
    $this->_classes = $registry->classes;
  }

  public function indexAction() {
    if (!$this->_auth->hasIdentity()) {
		$this->_redirect('/');
    }
  }
  
  public function forgotPasswordAction() {
  	require_once('recaptcha/recaptchalib.php');
  	if ($this->getRequest()->isPost()) {
		$post = $this->getRequest()->getPost();
		
  		$resp = recaptcha_check_answer("6LdwvscSAAAAALXytjYY5LvEEU7y-ZkBjttGwG5J",
											$_SERVER["REMOTE_ADDR"],
											$post['recaptcha_challenge_field'],
											$post['recaptcha_response_field']);
		$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn");
		if(!$resp->is_valid) {
			$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn",$resp->error);
		} else {
			$data = mysqli_query($this->_dbc,
				"SELECT id
				FROM accounts
				WHERE email = '$post[email]'"
			);
			
			if (mysqli_num_rows($data) == 0) {
				$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready',function(){
DEmessenger('Invalid email address','No account with the supplied email address was found.');
});
JS
			);
			} else {
				$data = mysqli_query($this->_dbc,
					"SELECT SUBSTRING(MD5(login),5,10) AS hash FROM accounts WHERE email = '$post[email]'");
				$data = mysqli_fetch_array($data);
				$hash = $data['hash'];
				$url = $_SERVER['SERVER_NAME'] . $this->view->url(array('controller' => 'account', 'action' => 'reset', 'hash' => $hash),null,true);
				$mail = new Zend_Mail();
				$mail->setBodyHtml(<<<EOHTML
<p>
	A password reset has been requested for this email address from www.dishonorelite.com. If you
	did not request a reset, please disregard this email.
</p>
<p>
	To reset your password, please click on this link <a href="$url">$url</a>.
</p>
EOHTML
);
				$mail->setFrom('admin@dishonorelite.com','Dishonor Elite');
				$mail->addTo($post[email]);
				$mail->setSubject('Password Reset Requested');
				$mail->send();
				$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready',function(){
DEmessenger('Reset request sent','Please check your email for further instructions.');
});
JS
			);
			}
		}
  	} else {
  		$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn");
  	}
  }
  
  public function resetAction() {
  	$hash = $this->getRequest()->getParam('hash');
  	if ($hash == null || strlen($hash) != 10)
  		$this->_redirect('/');
  		
  	
  	$data = mysqli_query($this->_dbc,
  		"SELECT id FROM accounts WHERE SUBSTRING(MD5(login),5,10) = '$hash'");
  	
  	if (mysqli_num_rows($data) == 0) {
  		$this->_redirect('/account');
  	} else {
  		$data = mysqli_fetch_array($data);
  		$n = rand(10e16, 10e20);
		$pw = strtoupper(base_convert($n, 10, 36));
		
  		$this->view->message = "Your password has been reset to: $pw.";
  		
  		mysqli_query($this->_dbc,
  			"UPDATE accounts
  			SET login = NOW(), password = MD5('$pw')
  			WHERE id = $data[id]"
  		);
  	}
  }

  public function addAction() {
    if ($this->_auth->hasIdentity()) {
		$this->_redirect('/account');
	}
  }

  public function addGuildAction() {

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    
    if ($this->getRequest()->isPost()) {
		$post = $this->getRequest()->getPost();
		
		$character = $this->_armory->getCharacter($post['main']);
		$gear = $character->getGear();
		$characterData = $character->getData();

		$slot = array($post['sl1'], $post['sl2']);
		
		if (array_key_exists($slot[0],$gear) || array_key_exists($slot[1],$gear)) {
			echo <<<JS
<script>			
DEmessenger('Gear check failed','Please ensure that the gear shown is unequipped and you have logged off.');
</script>
JS;
			return;
		}
		
		$class = $this->_classes[$characterData['class']];

		$client = new Zend_Http_Client('http://us.battle.net/wow/en/guild/proudmoore/dishonor%20elite/roster?&name=' . urlencode($post['main']));
		$response = $client->request();
		$html = $response->getBody();
		$dom = new Zend_Dom_Query($html);

		$results = $dom->query('div#roster.table table tbody tr.row1 td.rank');
		foreach($results as $result)
		$wowrank = trim($result->nodeValue);

		if ($wowrank == 'Guild Master' || $wowrank == 'Rank 1' || $wowrank == 'Rank 2' || $wowrank == 'Rank 3') {
		$rank = 30;
		} else if ($wowrank == 'Rank 4') {
			$rank = 20;
		} else {
		$rank = 10;
		}
		
		// Create Account and Character
		mysqli_query($this->_dbc,
			"INSERT INTO accounts
			(password, email, rank, timezone)
			VALUES (MD5('$post[password]'), '$post[email]', $rank, '$post[timezone]')"
		);
		
		$accountId = mysqli_insert_id($this->_dbc);
		$thumb = $character->getThumbnailURL();
		
		mysqli_query($this->_dbc,
			"INSERT INTO characters
			(name, account_id, rank, class, server, thumb)
			VALUES ('$characterData[name]', $accountId, $rank, '{$this->_classes[$characterData['class']]}', 'Proudmoore', '$thumb')"
		);
		
		$mainId = mysqli_insert_id($this->_dbc);
		
		mysqli_query($this->_dbc,
			"UPDATE accounts
			SET main_id = $mainId
			WHERE id = $accountId"
		);
				
		echo <<<JS
<script>			
DEmessenger('Account Creation Successful','You may login with your account information.');
setTimeout("window.location = baseUrl", 3000);
</script>
JS;
	} else {
		$this->_redirect('/');
	}
}

	public function addPublicAction() {
		require_once('recaptcha/recaptchalib.php');
		
		if ($this->getRequest()->isPost()) {
			$post = $this->getRequest()->getPost();
			$this->view->form = array('main' => $post['main'], 'server' => $post['server'], 'email' => $post['email']);
			$resp = recaptcha_check_answer("6LdwvscSAAAAALXytjYY5LvEEU7y-ZkBjttGwG5J",
											$_SERVER["REMOTE_ADDR"],
											$post['recaptcha_challenge_field'],
											$post['recaptcha_response_field']);
			$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn");
			if(!$resp->is_valid) {
				$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn",$resp->error);
			}

	      // Invalid Email Address
			else if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
				$this->view->error = 'Invalid Email Address';
			} else {
				
				$char = @$this->_armory->getCharacter($post['main'],$post['server']);
				
				if (!$this->charIsValid($char)) {
					$this->view->error = 'Character not found, please check your spelling and ensure your character can be found on the armory.';
					return;
				}
				
				$cdata = $char->getData();
				if ($cdata['guild']['name'] == 'Dishonor Elite')
				{
					$this->view->error = 'You are in Dishonor Elite. Please create a Guild Account.';
					return;
				}
				
				// Check for existing account
				$qdata = mysqli_query($this->_dbc,
					"SELECT COUNT(*) AS count
					FROM accounts a
					WHERE a.email = '$post[email]'
				");
				$qacc = mysqli_fetch_array($qdata);
				if ($qacc['count'] != 0) {
					$this->view->error = 'An account with this email address already exists.';
					return;
				}
		
				// Check for existing character
				$qdata = mysqli_query($this->_dbc,
					"SELECT COUNT(*) AS count
					FROM characters c
					WHERE c.name = '$cdata[name]' AND c.server = '$cdata[realm]'
				");
				$qchar = mysqli_fetch_array($qdata);
				if ($qchar['count'] != 0) {
					$this->view->error = 'An account with this character already exists.';
					return;
				}

				// MAKE A NEW PUBLIC ACCOUNT
				$thumb = $char->getThumbnailURL();
				$s_name = mysqli_real_escape_string($this->_dbc, $cdata['name']);
				$s_realm = mysqli_real_escape_string($this->_dbc, $cdata['realm']);
				$nchar = mysqli_query($this->_dbc,
					"INSERT INTO characters
					(name, rank, class, server, thumb)
					VALUES ('$s_name', 0, '{$this->_classes[$cdata['class']]}', '$s_realm', '$thumb')"
				);
				if (!$nchar) {
					var_dump(mysqli_error($this->_dbc));die();
				}
				$mainId = mysqli_insert_id($this->_dbc);
				
				
				mysqli_query($this->_dbc,
					"INSERT INTO accounts
					(password, email, rank, timezone, main_id)
					VALUES (MD5('$post[password]'), '$post[email]', 0, '$post[timezone]', $mainId)"
				); 
				
				$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready',function(){
DEmessenger('Account Creation Successful','You may login with your account information.');
setTimeout("window.location = '/'", 4000);
});
JS
				);
			}
		} else {
			$this->view->form = array('main' => '', 'server' => '', 'email' =>  '');
			$this->view->recaptcha = recaptcha_get_html("6LdwvscSAAAAAEOhhx-ImtpJcURkraL2BLqT7cSn");
		}
	}

	

	public function viewAction() {
		// action body
	}

	public function editAction() {
		if (!$this->_auth->hasIdentity()) {
			$this->_redirect('/');
		}
	}

	public function changeEmailAction()
	{
		if (!$this->_auth->hasIdentity()) {
			$this->_redirect('/');
		}
			
		$identity = $this->_auth->getIdentity();
		$result = mysqli_query($this->_dbc,
			"SELECT email
			FROM accounts
			WHERE id = $identity[id]"
		);
		
		$result = mysqli_fetch_array($result);
		
		$this->view->email = $result['email'];

		if ($this->getRequest()->isPost())
		{
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$post = $this->getRequest()->getPost();
			$code = substr(md5(md5($post['email'])),10,10);
			if (!filter_var($post['email'], FILTER_VALIDATE_EMAIL))	{
				echo <<<JS
<script>			
DEmessenger('Invalid Email Address');
</script>
JS;
			} else {
				//change email
				mysqli_query($this->_dbc,
					"UPDATE accounts
					SET email='$post[email]'
					WHERE id=$identity[id]"
				);
				$this->_auth->clearIdentity();

				echo <<<JS
<script>			
DEmessenger('Email Successfully Changed','You will be logged out shortly.');
setTimeout("window.location = '.'", 3500);
</script>
JS;
			}
		}
	}

	public function changePwAction() {
		if (!$this->_auth->hasIdentity()) {
			$this->_redirect('/');
		}
			
		if ($this->getRequest()->isPost()) {
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			$post = $this->getRequest()->getPost();
			
			// Same passwords
			if ($post['newpw'] == $post['oldpw']) {
				echo <<<JS
<script>			
DEmessenger('New password is same as curent', 'Why....');
</script>
JS;
				return;
			}
			
			$identity = $this->_auth->getIdentity();
			mysqli_query($this->_dbc,
				"UPDATE accounts
				SET password=MD5('$post[newpw]')
				WHERE id=$identity[id] AND password=MD5('$post[oldpw]')"
			);
			
			if (mysqli_affected_rows($this->_dbc)) {
				echo <<<JS
<script>			
DEmessenger('Password Successfully Changed');
setTimeout("window.location = '.'", 3500);
</script>
JS;
				} else {
				//wrong pass
				echo <<<JS
<script>			
DEmessenger('Incorrect Password');
</script>
JS;
			}
		}
	}

	private function charIsValid($char) {
		return array_key_exists('name',$char->getData());
	}

	public function requestArmorAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		header('Content-type: text/html; charset=utf-8');
		if ($this->getRequest()->isPost()) {
			$post = $this->getRequest()->getPost();
			$this->_helper->layout->disableLayout();
			
			$char = @$this->_armory->getCharacter($post['main']);
			
			if (!$this->charIsValid($char)) {
				echo <<<JS
<script>			
DEmessenger('Character not found','Please check your spelling and ensure your character can be found on the armory.');
</script>
JS;
				return;	
			}
			
			$charData = $char->getData();
			$gear = $char->getGear();
	
			// Guild check
			if ($charData['guild']['name'] != 'Dishonor Elite') {
				echo <<<JS
<script>			
DEmessenger('Character not in Dishonor Elite','Only current guild members may create guild accounts.');
</script>
JS;
				return;
			}

			// Not enough gear on
			if (count($gear) < 4) {
				
				echo <<<JS
<script>
DEmessenger('Unable to process character','Please equip more gear in order to verify ownership.');
</script>
JS;
				return;
			}
			
			// Check for existing account
			$qdata = mysqli_query($this->_dbc,
				"SELECT COUNT(*) AS count
				FROM accounts a
				WHERE a.email = '$post[email]'
			");
			$qacc = mysqli_fetch_array($qdata);
			if ($qacc['count'] != 0) {
				echo <<<JS
<script>			
DEmessenger('Account already exists','An account with this email address already exists.');
</script>
JS;
				return;
			}
	
			// Check for existing character
			$qdata = mysqli_query($this->_dbc,
				"SELECT COUNT(*) AS count
				FROM characters c
				WHERE c.name = '$post[main]'
			");
			$qchar = mysqli_fetch_array($qdata);
			if ($qchar['count'] != 0) {
				echo <<<JS
<script>			
DEmessenger('Character already exists','An account with this character already exists.');
</script>
JS;
				return;
			}

			// Passed all checks, get gear
			$slot = $this->getRandomSlots($gear, ord(substr($post['main'],-1)));
			$this->_helper->viewRenderer->setNoRender(false);
			$this->view->slot = $slot;
			$this->view->armor = array($this->_armory->getItem($gear[$slot[0]]['id']),
									   $this->_armory->getItem($gear[$slot[1]]['id']));
			
		}
	}
	
	private function getRandomSlots($gear, $seed) {
		srand($seed);
		$slot[0] = array_rand($gear);
  		$slot[1] = array_rand($gear);
   		while ($slot[0] == 'averageItemLevel' || $slot[0] == 'averageItemLevelEquipped') {
			$slot[0] = array_rand($gear);	
    	}
    	while ($slot[1] == 'averageItemLevel' || $slot[1] == 'averageItemLevelEquipped' || $slot[1] == $slot[0]) {
    		$slot[1] = array_rand($gear);
    	}
    	return $slot;
	}

	public function deleteAction()
	{
	}
}
?>
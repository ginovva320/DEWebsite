<?php
class Zend_View_Helper_Login implements Zend_View_Helper_Interface {
	
	function login() {
		$base = Zend_Controller_Front::getInstance()->getBaseUrl();

		if(Zend_Auth::getInstance()->hasIdentity())
		{
			$id = Zend_Auth::getInstance()->getIdentity();
			$url = $this->view->url(array('controller' => 'account'), null, true);
			$admin = '';
			if ($id['rank'] > 29) {
				$aUrl = $this->view->url(array('controller' => 'admin'), null, true);
				$admin = "<a href=$aUrl>Admin</a> | ";
			}
			$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready', function() {
	$('logoutlink').addEvent('click', function(e) {
		e.stop();
		new Request({
				url : baseUrl+'/authentication/logout',
				onSuccess : function() {
					location.reload(true);
				}
		}).send();
	});
});
JS
			);
			return <<<EOHTML
<header id=user>
<div id=user_links>
<a href=$url><h1 class={$id['class']}>{$id['name']}</h1></a>
$admin
<a id=logoutlink href={$base}/authentication/logout>Logout</a>
</div>
<img class='wow-icon' src=$id[thumb] />
</header>
EOHTML;
		}
		else
		{
			$url = $this->view->url(array('controller' => 'account', 'action' => 'add'), null, true);
			$pwUrl = $this->view->url(array('controller' => 'account', 'action' => 'forgot-password'), null, true);
			$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready', function() {
	var password = $$('[name=login_password]')[0];
	
	$('showlogin').addEvent('click', function(e) {
		e.stop();
		$('container').fade(0.1);
		$('login_box').reveal();
	});
	
	$('login_cancel').addEvent('click', function(e) {
		e.stop();
		$('login_box').dissolve();
		$('container').fade('in');
	});
	
	$('login_form').addEvent('submit', function(e) {
		e.stop();
		new Request({
				url : baseUrl+'/authentication/login',
				onSuccess : function(response) {
					if(response == 'true')
						location.reload(true);
					else
					{
						DEmessenger(
							'Invalid Login',
							'Email/password combination not found'
						);
						password.value = null;
					}
				}
		}).post($('login_form')).send();
	});
});
JS
			);
			return <<<EOHTML
<section id=loginlinks>
<ul>
	<li><a id=showlogin href=#>Login</a></li>
	<li><a href=$url>Create an account</a></li>
</ul>
</section>
<div id=login_box>
<header><h1>Login</h1></header>
<form id=login_form method=post action=/authentication/login >
<table>
	<tbody>
		<tr>
			<th>Email</th><td><input type=text name=email /></td>
		</tr>
		<tr>
			<th>Password</th><td><input type=password name=password /></td>
		</tr>
		<tr>
			<th>Remember Me</th><td><input type=checkbox name=remember value=true /></td>
		</tr>
		<tr>
			<th></th><td><input type=submit value=Login /><input type=button value=Cancel id=login_cancel /></td>
		</tr>
		<tr>
			<th></th><td><a href='$pwUrl'>Forgot My Password</a></td>
		</tr>
	</tbody>
</table>
</form>
</div>
EOHTML;
		}
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

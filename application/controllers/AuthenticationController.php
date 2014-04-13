<?php

class AuthenticationController extends Zend_Controller_Action
{

	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
	}

	public function indexAction()
	{
		if (Zend_Auth::getInstance()->hasIdentity())
		{
			$this->_redirect('/');
		}

		$req = $this->getRequest();
		$form = new Application_Form_LoginForm(array(
            'action' => 'authentication',
            'method' => 'post',
		));

		if ($req->isPost() && $form->isValid($req->getPost()))
		{
			$email = $req->getParam('email');
			$password = md5($req->getParam('password'));
				
			$authAdapter = new DEAuthAdapter($email, $password);
			$result = $this->_auth->authenticate($authAdapter);
			if(!$result->isValid()) {
				$form->setDescription('Invalid Login');
			} else {
				$this->_redirect('/');
			}
		}

		$this->view->form = $form;
	}

	public function loginAction()
	{
		$req = $this->getRequest();
		$this->_helper->layout->disableLayout();
				
		if ($req->isPost())
		{
			$email = $req->getParam('email');
			$password = md5($req->getParam('password'));
			$remember = $req->getParam('remember');
			
			$authAdapter = new DEAuthAdapter($email, $password);
			$result = $this->_auth->authenticate($authAdapter);
			if(!$result->isValid()) {
				echo 'false';
			} else {
				echo 'true';
				
				$session = new Zend_Session_Namespace('Zend_Auth');
				$session->setExpirationSeconds(24*3600);
				if ($remember) {
					Zend_Session::rememberMe(24*3600*31*12);
				} else {
					Zend_Session::forgetMe();
				}
			}
		}
		else
			$this->_redirect('/');
	}

	public function logoutAction()
	{
		Zend_Auth::getInstance()->clearIdentity();
		$this->_redirect('/');
	}
}


class DEAuthAdapter implements Zend_Auth_Adapter_Interface
{
	private $_email;
	private $_password;
	private $_em;
	
	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
	}
	
	public function __construct($email, $password)
	{
		$this->_email = filter_var($email, FILTER_SANITIZE_EMAIL);
		$this->_password = filter_var($password, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		$this->init();
	}
	
	public function authenticate()
	{
		$data = mysqli_query($this->_dbc, 
			"SELECT a.id, a.rank, a.timezone, c.name, c.class, c.thumb, COUNT(*) as count
			FROM accounts a
			JOIN characters c
			ON a.main_id = c.id
			WHERE a.email = '$this->_email'
			AND a.password = '$this->_password'
		");

		$account = mysqli_fetch_array($data);
		
		if($account['count'] > 0) {
			mysqli_query($this->_dbc,"UPDATE accounts SET login = NOW() WHERE email = '{$this->_email}'");
			return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $account);
		}
		else
			return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, null);
	}
}



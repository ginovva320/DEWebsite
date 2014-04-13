<?php
class Application_Form_LoginForm extends Zend_Form
{
	public $elementDecorators = array(
        'ViewHelper',
        'Errors',
	array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
	array('Label', array('tag' => 'td')),
	array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
	);

	public $buttonDecorators = array(
        'ViewHelper',
	array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
	array(array('label' => 'HtmlTag'), array('tag' => 'td', 'placement' => 'prepend')),
	array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
	);

	public function init()
	{
		$this->addElement('text', 'email', array(
            'decorators' => $this->elementDecorators,
			'label' => 'Email Address',
			'required' => true,
			'filters' => array('StringTrim')
		));
		
		$this->addElement('password', 'password', array(
            'decorators' => $this->elementDecorators,
			'label' => 'Password',
			'required' => true,
			'filters' => array('StringTrim')
		));

		$this->addElement('submit', 'submit', array(
            'decorators' => $this->buttonDecorators,
            'label' => 'Login'
            ));
	}

	public function loadDefaultDecorators()
	{
		$this->setDecorators(array(
            'FormElements',
		array('HtmlTag', array('tag' => 'table')),
            'Form',
		));
	}
}
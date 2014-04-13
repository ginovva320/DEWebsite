<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
/**
     * generate registry
     * @return Zend_Registry
     */
    protected function _initRegistry(){
        $registry = Zend_Registry::getInstance();
        return $registry;
    }
 
    /**
     * Register namespace Default_
     * @return Zend_Application_Module_Autoloader
     */
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Application_',
            'basePath'  => dirname(__FILE__),
        ));
      	/*  
        if(Zend_Auth::getInstance()->hasIdentity())
		{
			Zend_Registry::set('role', Zend_Auth::getInstance()->getStorage()->read()->role);
		} else {
			Zend_Registry::set('role', 'guests');
		}
	
		$acl = new My_Model_DishonorAcl;
		
		$fc = Zend_Controller_Front::getInstance();
		$fc->registerPlugin(new My_Resource_AccessCheck($acl));
        */
        return $autoloader;
    }


    protected function _initDatabase() {
    	$settings = $this->getOption('database');
    	$dbc = mysqli_connect($settings['conn']['host'],
    				   $settings['conn']['user'],
    				   $settings['conn']['pass'],
    				   $settings['conn']['dbname']);
    	$registry = Zend_Registry::getInstance();
    	$registry->dbc  = $dbc;
    	
    	return $dbc;
    }
    
    protected function _initArmory() {
    	
    	$settings = $this->getOption('database');
    	$GLOBALS['wowarmory']['db']['driver'] = 'mysql'; // Dont change. Only mysql supported so far.
	    $GLOBALS['wowarmory']['db']['hostname'] = $settings['conn']['host']; // Hostname of server. 
	    $GLOBALS['wowarmory']['db']['dbname'] = $settings['conn']['dbname']; //Name of your database
	    $GLOBALS['wowarmory']['db']['username'] = $settings['conn']['user']; //Insert your database username
	    $GLOBALS['wowarmory']['db']['password'] = $settings['conn']['pass']; //Insert your database password

	    require_once('wowarmoryapi-code/BattlenetArmory.class.php');
	    
	    $armory = new BattlenetArmory('US','Proudmoore');
	    $registry = Zend_Registry::getInstance();
    	$registry->armory  = $armory;
    	$registry->races = array(
    		'1' => 'Human',
    		'2' => 'Orc',
    		'3' => 'Dwarf',
    		'4' => 'Night Elf',
    		'5' => 'Undead',
    		'6' => 'Tauren',
    		'7' => 'Gnome',
    		'8' => 'Troll',
    		'9' => 'Goblin',
    		'10' => 'Blood Elf',
    		'11' => 'Draenei',
    		'22' => 'Worgen'
    	);
    	
    	$registry->classes = array(
    		'3' => 'hunter',
    		'4' => 'rogue',
    		'1' => 'warrior',
    		'2' => 'paladin',
    		'7' => 'shaman',
    		'8' => 'mage',
    		'5' => 'priest',
    		'6' => 'deathknight',
    		'11' => 'druid',
    		'9' => 'warlock'
    	);
    	
		return $armory;	    
    }
    
	protected function _initViewHelpers()
	{
		$this->bootstrap('layout');
		$layout = $this->getResource('layout');
		$view = $layout->getView();
		
		$view->doctype('HTML5');
		$view->headMeta()->appendHttpEquiv('Content-type', 'text/html;charset=utf-8')
						 ->appendName('description', 'Dishonor Elite - Proudmoore US')
						 ->appendName('keywords', 'dishonor, elite, world, of, warcraft, proudmoore, raiding');
		
		$view->headTitle()->setSeparator(' :: '); 
		$view->headTitle('Dishonor Elite');
	}
	
	protected function _initTimezone() {
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
			date_default_timezone_set($identity['timezone']);
		} else {
			date_default_timezone_set('America/Los_Angeles');
		}
	}
}


<?php

class AdminController extends Zend_Controller_Action
{

    public function init()
    {
        $registry = Zend_Registry::getInstance();
		$this->_dbc = $registry->dbc;
		$this->_auth = Zend_Auth::getInstance();
		if ($this->_auth->hasIdentity()) {
			$this->identity = $this->_auth->getIdentity();
			if ($this->identity['rank'] < 30) {
				$this->_redirect("/");
			}
		} else {
			$this->_redirect("/");
		}
    }

    public function indexAction()
    {
        // action body
    }
    
    public function editSlideAction() {
    	
    	$id = $this->getRequest()->id;
    	
    	if ($id == null) {
    		$this->_redirect('/admin');
    	}
    	
    	if ($this->getRequest()->isPost()) {
    		$post = $this->getRequest()->getPost();
    		
    		mysqli_query($this->_dbc,
    			"UPDATE slides
    			SET title = '$post[title]', caption = '$post[caption]', visibility = $post[vis]
    			WHERE id = $id"
    		);
    		
    		$this->view->minifyHeadScript()->appendScript("window.addEvent('domready',function(){DEmessenger('Slide Updated');});");
    	}
    	
    	$result = mysqli_query($this->_dbc,
    		"SELECT s.visibility, s.title, s.caption, a.id AS a_id
    		FROM slides s
    		JOIN attachments a
    		ON a.id = s.attachment_id
    		WHERE s.id = $id"
    	);
    	
    	$this->view->slide = mysqli_fetch_array($result);
    	
    	
    }

    public function addSlideAction() {
    	$form = new SlideForm();
    	$this->view->form = $form;
    	
    	if ($this->getRequest()->isPost()) {
    		$formData = $this->getRequest()->getPost();
    		if ($form->isValid($formData)) {
    			$upload = new Zend_File_Transfer_Adapter_Http();
    			$upload->setDestination("img/temp/");
    			
    			try {
    				$upload->receive();
    			} catch (Zend_File_Transfer_Exception $e) {
    				$e->getMessage();
    			}
    			
    			$uploadedData = $form->getValues();
    			
    			$name = $upload->getFileName('slide_image');
				$size = $upload->getFileSize('slide_image');
				$mimeType = $upload->getMimeType('slide_image');
				$ahoih = explode('.',$name);
				$mimeType = $ahoih[1];
				if ($mimeType != 'png') {
					$form->getElement('slide_image')->addError('Image must be a png file. ' . $mimeType . ' given.');
					unlink($name);
					return;
				} else {
				
					mysqli_query($this->_dbc,
						"INSERT INTO attachments
						(filetype, size, account_id, time)
						VALUES ('$mimeType', '$size', {$this->identity['id']}, UNIX_TIMESTAMP())"
					);
					
					$newId = mysqli_insert_id($this->_dbc);
									
					$renameFile = "slide_$newId.png";
					
					$fullFilePath = 'img/slideshow/'.$renameFile;
					
					$filterFile = new Zend_Filter_File_Rename(array('target' => $fullFilePath, 'overwrite' => true));
					$filterFile->filter($name);
	
					mysqli_query($this->_dbc,
						"INSERT INTO slides
						(sort, visibility, attachment_id, title, caption)
						VALUES (999, $uploadedData[vis], $newId, '$uploadedData[title]', '$uploadedData[desc]')"
					);
					
					$newId = mysqli_insert_id($this->_dbc);
					
					mysqli_query($this->_dbc,
						"UPDATE slides
						SET sort = $newId
						WHERE id = $newId"
					);
					
					$this->view->minifyHeadScript()->appendScript(<<<JS
window.addEvent('domready', function(){
	DEmessenger('Slide successfully added','Go to Admin >> Manage Slides to change the order of slides.');
});
JS
					);
				}
    		} else {
    	 		$form->populate($formData);
    	 	}
    	}
    }
    
    public function slidesAction() {
    	if ($this->getRequest()->isPost()) {
    		$this->_helper->viewRenderer->setNoRender();
			$this->_helper->layout->disableLayout();
    		$post = $this->getRequest()->getPost();
    		
    		$ids = explode(',',$post['sort_order']);
    		$query = '';
    		foreach ($ids as $index => $id) {
    			$id = (int) $id;
    			if ($id != '') {
    				$query .= "UPDATE slides SET sort = " . ($index + 1) . ' WHERE id = ' . $id . ';';
    			}
    		}
    		mysqli_multi_query($this->_dbc,$query);
    	} else {
	    	$this->view->slides = mysqli_query($this->_dbc,
	    		"SELECT s.attachment_id, s.title, s.caption, s.visibility, s.sort, c.name, c.class, at.time, s.id
				FROM slides s
				JOIN attachments at
				ON at.id = s.attachment_id
				JOIN accounts ac
				ON at.account_id = ac.id
				JOIN characters c
				ON ac.main_id = c.id
				ORDER BY sort ASC"
	    	);
    	}	
    }
    
    public function deleteSlideAction() {
    	$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		if ($this->getRequest()->isPost()) {
			$post = $this->getRequest()->getPost();
			$result = mysqli_query($this->_dbc,
				"SELECT a.id
				FROM slides s
				JOIN attachments a
				ON s.attachment_id = a.id
				WHERE s.id = $post[id]"
			);
			$result = mysqli_fetch_array($result);
			unlink('img/slideshow/slide_' . $result['id'] . '.png');
			mysqli_multi_query($this->_dbc,
				"DELETE FROM slides
				WHERE id = $post[id];
				DELETE FROM attachments
				WHERE id = $result[id]"
			);
		}
    }

}

class SlideForm extends Zend_Form
{
	public $elementDecorators = array(
		'ViewHelper',
		'Errors',
		array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
		array('Label', array('tag' => 'th')),
		array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
	);
	
	public $buttonDecorators = array(
		'ViewHelper',
		array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
		array(array('label' => 'HtmlTag'), array('tag' => 'th', 'placement' => 'prepend')),
		array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
	);
    
	 public function __construct($options = null)
	 {
		parent::__construct($options);
		// setting Form name, Form action and Form Ecryption type
		$this->setName('slide_form');
		$this->setMethod('post');
		$this->setAction("");
		$this->setAttrib('enctype', 'multipart/form-data');
		
		 // creating object for Zend_Form_Element_File
		$doc_file = new Zend_Form_Element_File('slide_image');
		$doc_file->setLabel('Image')->setDescription('Image must be 1000px by 300px, png.')
			->setRequired(true)->setDecorators(array(
				'File',
				'Description',
				'Errors',
				array(array('data'=>'HtmlTag'), array('tag' => 'td')),
				array('Label', array('tag' => 'th')),
				array(array('row'=>'HtmlTag'),array('tag'=>'tr'))
				));
		
		$title = new Zend_Form_Element_Text('title');
		$title->setLabel('Slide Title')
			->setRequired(true)
			->setAttrib('size','40')
			->setDecorators($this->elementDecorators);
		
		$desc = new Zend_Form_Element_Textarea('desc');
		$desc->setLabel('Slide Caption')
			->setRequired(true)
			->setAttrib('cols','30')
			->setAttrib('rows', '2')
			->setDecorators($this->elementDecorators);
			
		$vis = new Zend_Form_Element_Radio('vis');
		$vis->setLabel('Visibility')
			->setRequired(true)
			->addMultiOptions(array(
				'40' => 'Disabled',
				'30' => 'Admin',
				'20' => 'Raider',
				'10' => 'Guild Member',
				'0' => 'Public - Logged In ',
				'-1' => 'Anyone'
			))
			->setDecorators($this->elementDecorators);
		
		// creating object for submit button
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel('Upload File')
			->setAttrib('id', 'submitbutton')
			->setDecorators($this->buttonDecorators);
		
		// adding elements to form Object
		$this->addElements(array($title, $desc, $vis, $doc_file, $submit));
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
?>
--TEST--
Links
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('genre_id');
var_dump($el->_options['0']['text']);
$do =& DB_DataObject::factory('movie');
function prepDO(&$do, $field) {
	if ($field == 'genre_id') {
		$do->fb_linkDisplayFields = array('name');
	}
}
$do->fb_prepareLinkedDataObjectCallback = 'prepDO';
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('genre_id');
var_dump($el->_options['0']['text']);
$do =& DB_DataObject::factory('movie');
function prepDO2(&$do, $field) {
	switch ($field) {
	case 'genre_id':
		$do->fb_linkDisplayFields = array('name', 'id');
		break;
	case 'linkTest_id':
		$do->fb_linkDisplayFields = array('name', 'name2', 'linkTest2_id');
		break;
	case 'linkTest2_id':
		$do->fb_linkDisplayFields = array('name', 'name2');
		break;
	}
}
$do->fb_prepareLinkedDataObjectCallback = 'prepDO2';
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('genre_id');
var_dump($el->_options['0']['text']);

$do =& DB_DataObject::factory('movie');
$do->fb_prepareLinkedDataObjectCallback = 'prepDO2';
$do->fb_linkDisplayLevel = 1;
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('linkTest_id');
var_dump($el->_options['1']['text']);

$do =& DB_DataObject::factory('movie');
$do->fb_prepareLinkedDataObjectCallback = 'prepDO2';
$do->fb_linkDisplayLevel = 2;
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('linkTest_id');
var_dump($el->_options['1']['text']);
?>
--EXPECT--
string(1) "1"
string(6) "Action"
string(9) "Action, 4"
string(15) "1name, 2name, 1"
string(33) "1name, 2name, (1, 1 name, 2 name)"

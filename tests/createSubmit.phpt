--TEST--
create submit
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
var_dump(isset($form->_elementIndex['__submit__']));
$do->fb_createSubmit = false;
$fb =& DB_DataObject_FormBuilder::create($do);
$fb->createSubmit = false;
$form =& $fb->getForm();
var_dump(isset($form->_elementIndex['__submit__']));
?>
--EXPECT--
bool(true)
bool(false)

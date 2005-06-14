--TEST--
getForm
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
var_dump(array_search('title', $form->_required) !== false);
?>
--EXPECT--
bool(true)

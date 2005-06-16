--TEST--
primary key is hidden
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('id');
echo $el->getAttribute('type')."\n";
$do->fb_hidePrimaryKey = false;
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$el =& $form->getElement('id');
echo $el->getAttribute('type')."\n";
?>
--EXPECT--
hidden
text

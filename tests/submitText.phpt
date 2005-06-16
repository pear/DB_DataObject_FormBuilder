--TEST--
submit text
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$submit =& $form->getElement('__submit__');
var_dump($submit->getAttribute('value'));

$do->fb_submitText = 'Save Changes';
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
$submit =& $form->getElement('__submit__');
var_dump($submit->getAttribute('value'));
?>
--EXPECT--
string(6) "Submit"
string(12) "Save Changes"

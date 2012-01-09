--TEST--
create submit
--SKIPIF--
<?php require_once dirname(__FILE__).'/config.php'; ?>
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
if (PEAR::isError($do)) {
    die($do->getMessage());
}
$fb =& DB_DataObject_FormBuilder::create($do);
if (PEAR::isError($fb)) {
    die($fb->getMessage());
}
$form =& $fb->getForm();
var_dump($form->elementExists('__submit__'));
$do->fb_createSubmit = false;
$fb =& DB_DataObject_FormBuilder::create($do);
$fb->createSubmit = false;
$form =& $fb->getForm();
var_dump($form->elementExists('__submit__'));
?>
--EXPECT--
bool(true)
bool(false)

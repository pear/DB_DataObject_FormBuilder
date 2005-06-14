--TEST--
getForm
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
echo strtolower(get_class($form)).'
';
foreach ($do->table() as $field => $type) {
    if (!isset($form->_elementIndex[$field])) {
        echo $field.' missing
';
    }
}
?>
--EXPECT--
html_quickform

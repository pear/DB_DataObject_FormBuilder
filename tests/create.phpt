--TEST--
DB_DO_FB::create
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
echo strtolower(get_class($fb));
?>
--EXPECT--
db_dataobject_formbuilder
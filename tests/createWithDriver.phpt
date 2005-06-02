--TEST--
DB_DO_FB::create($options, $driver)
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do, array('formHeaderText' => 'MOVIE Header',
                                                    'preDefOrder' => array('title', 'genre_id')),
                                         'QuickForm');
echo strtolower(get_class($fb)).'
';
echo strtolower(get_class($fb->_form));
?>
--EXPECT--
db_dataobject_formbuilder
db_dataobject_formbuilder_quickform
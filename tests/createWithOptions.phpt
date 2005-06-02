--TEST--
DB_DO_FB::create($options)
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do, array('formHeaderText' => 'MOVIE Header',
                                                    'preDefOrder' => array('title', 'genre_id')));
echo strtolower(get_class($fb)).'
';
print_r($fb->formHeaderText);
echo '
';
print_r($fb->preDefOrder);
?>
--EXPECT--
db_dataobject_formbuilder
MOVIE Header
Array
(
    [0] => title
    [1] => genre_id
)

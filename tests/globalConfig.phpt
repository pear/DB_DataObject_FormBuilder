--TEST--
Global Config
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$config =& PEAR::getStaticProperty('DB_DataObject_FormBuilder', 'options');
$config = array('formHeaderText' => 'Global option',
                'linkDisplayLevel' => '5',
                'preDefOrder' => 'title,genre',
                'elementTypeMap' => 'text:textType,date:dateType',
                'linkDisplayFields' => array('genre_id', 'movie'));
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
//Text option with no default
echo $fb->formHeaderText.'
';
//Text option with default
echo $fb->linkDisplayLevel.'
';
//String Array with no keys
print_r($fb->preDefOrder);
echo '
';
//String Array with keys
print_r($fb->elementTypeMap);
echo '
';
//Array
print_r($fb->linkDisplayFields);
echo '
';
?>
--EXPECT--
Global option
5
Array
(
    [0] => title
    [1] => genre
)

Array
(
    [text] => textType
    [date] => dateType
)

Array
(
    [0] => genre_id
    [1] => movie
)

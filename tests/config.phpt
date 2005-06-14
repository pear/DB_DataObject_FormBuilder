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
$do->fb_textFields = array('notes');
$fb =& DB_DataObject_FormBuilder::create($do, array('linkDisplayLevel' => 4,
                                                    'elementNamePrefix' => 'abcd',
                                                    'elementNamePostfix' => 'efgh'));
$fb->elementNamePostfix = 'ijkl';
$fb->crossLinkSeparator = '<br/><br/>';
$fb->textFields = array('title');
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
echo $fb->elementNamePrefix.'
';
echo $fb->elementNamePostfix.'
'.$fb->crossLinkSeparator.'
';
print_r($fb->textFields);
$fb->populateOptions();
print_r($fb->textFields);
?>
--EXPECT--
Global option
4
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

abcd
ijkl
<br/><br/>
Array
(
    [0] => title
)
Array
(
    [0] => notes
)
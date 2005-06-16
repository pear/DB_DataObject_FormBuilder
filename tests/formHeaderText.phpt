--TEST--
formHeaderText
--FILE--
<?php
include(dirname(__FILE__).'/config.php');
$do =& DB_DataObject::factory('movie');
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
foreach ($form->_elements as $el) {
    if (is_a($el, 'HTML_QuickForm_header')) {
        echo $el->_text."\n";
        break;
    }
}

$do->fb_formHeaderText = 'Movie Header';
$fb =& DB_DataObject_FormBuilder::create($do);
$form =& $fb->getForm();
foreach ($form->_elements as $el) {
    if (is_a($el, 'HTML_QuickForm_header')) {
        echo $el->_text."\n";
        break;
    }
}
?>
--EXPECT--
movie
Movie Header

<?php
/**
 * This is an HTML_QuickForm element for internal FormBuilder use only. It
 * creates a select element for a foreign key / link which also has a hidden
 * div with a form for a new linked record in it. All processing is done in
 * the FormBuilder main class.
 *
 * PHP Versions 4 and 5
 *
 * @category DB
 * @package  DB_DataObject_FormBuilder
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @author   Justin Patrin <papercrane@reversefold.com>
 * @version  $Id$
 */

require_once('HTML/QuickForm/select.php');

/**
 * This class creates a select box with a hidden div beneath it which is shown when
 * "--New Value--" is selected in the select box. The hidden div includes another
 * form for creating a new linekd record. This class is only to be used internally
 * for DB_DataObject_FormBuilder.
 */
class DB_DataObject_FormBuilder_QuickForm_PopupSelect extends HTML_QuickForm_select {

    /**
     * @var DB_DataObject_FormBuilder The formbuilder we're a part of
     * @access private
     */
    var $_fb;

    /**
     * @var string the name of the actual FormBuilder field this is for (not the full field name with prefix / postfix)
     * @access private
     */
    var $_fieldName;

    /**
     * Sets the FormBuilder object we're creating the element for. *MUST* be called before rendering.
     *
     * @param DB_DataObject_FormBuilder the FormBuilder object
     */
    function setFormBuilder(&$fb) {
        $this->_fb =& $fb;
    }

    /**
     * Sets the field name we're creating the element for. This is the field name
     * from the actual table, not the element name which may have a prefix / postfix
     *
     * @param string the name of the table field
     */
    function setFieldName($fieldName) {
        $this->_fieldName = $fieldName;
    }

    /**
     * renders the element
     *
     * @return string the HTML for the element
     */
    function toHtml() {
        static $recLevel = 0;
        if (!$recLevel) {
            $links = $this->_fb->_do->links();
            if (isset($links[$this->_fieldName])) {
                list($table,) = explode(':', $links[$this->_fieldName]);
                $this->addOption($this->_fb->linkNewValueText, $this->_fb->linkNewValueText);
                $this->updateAttributes(array('onchange' => 'DB_DataObject_FormBuilder_QuickForm_PopupSelect_onchange_'.$this->getName().'_'.$table.'(this)'));
                $this->updateAttributes(array('id' => $this->getName()));
            }
        }
        $output = parent::toHtml();
        if (!$recLevel && isset($table)) {
            ++$recLevel;
            $this->_fb->_form->_prepareForLinkNewValue($this->_fieldName, $table);
            require_once('HTML/QuickForm/Renderer/Default.php');
            $renderer = new HTML_QuickForm_Renderer_Default();
            $this->_fb->_form->_linkNewValueForms[$this->_fieldName]->accept($renderer);
            $output .= '
<style>
.hidden {
  visibility: hidden;
  overflow: hidden;
  display: none;
}
</style>
<div id="'.$this->getName().'_'.$table.'" class="hidden">
'.preg_replace('!</?form[^>]*?>!i', '', $renderer->toHtml()).'
</div>
<script type="text/javascript">
function DB_DataObject_FormBuilder_QuickForm_PopupSelect_onchange_'.$this->getName().'_'.$table.'(sel) {
  if(sel.value == "'.$this->_fb->linkNewValueText.'") {
    document.getElementById("'.$this->getName().'_'.$table.'").className = "";
  } else {
    document.getElementById("'.$this->getName().'_'.$table.'").className = "hidden";
  }
}
DB_DataObject_FormBuilder_QuickForm_PopupSelect_onchange_'.$this->getName().'_'.$table.'(document.getElementById("'.$this->getName().'"));
</script>
';
            --$recLevel;
        }
        return $output;
    }
}

?>
<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author:  Markus Wolff <mw21st@php.net>                               |
// +----------------------------------------------------------------------+

/**
 * This is a driver class for the DB_DataObject_FormBuilder package.
 * It uses HTML_QuickForm to render the forms.
 *
 * @package  DB_DataObject_FormBuilder
 * @author   Markus Wolff <mw21st@php.net>
 * @version  $Id$
 */

require_once ('HTML/QuickForm.php');

class DB_DataObject_FormBuilder_QuickForm extends DB_DataObject_FormBuilder
{
    /**
     * DB_DataObject_FormBuilder_QuickForm::DB_DataObject_FormBuilder_QuickForm()
     *
     * The class constructor.
     *
     * @param object $do      The DB_DataObject-derived object for which a form shall be built
     * @param array $options  An optional associative array of options.
     * @access public
     */
    function DB_DataObject_FormBuilder_QuickForm(&$do, $options = false)
    {
        // Call parent class constructor.
        parent::DB_DataObject_FormBuilder($do,$options);
    }

    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createFormObject()
     *
     * Creates a QuickForm object to be used by _generateForm().
     *
     * @param string $formName  The name of the form
     * @param string $method    Method for transferring form data over HTTP (GET|POST)
     * @param string $action    The script to transfer the form data to
     * @param string $target    Name of the target frame/window to use to display the "action" script
     * @return object           The HTML_QuickForm object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createFormObject($formName, $method, $action, $target)
    {
        // If there is an existing QuickForm object, and the form object should not just be
        // appended, use that one. If not, make a new one.
        if (is_a($this->_form, 'html_quickform') && $this->_appendForm == false) {
            $form =& $this->_form;
        } else {
            $form =& new HTML_QuickForm($formName, $method, $action, $target);
        }
        return $form;
    }
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_addFormHeader()
     *
     * Adds a header to the given form. Will use the header defined in the "formHeaderText" property.
     * Used in _generateForm().
     *
     * @param object $form    The QuickForm object to add the header to
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _addFormHeader(&$form)
    {
        // Add a header to the form - set addFormHeader property to false to prevent this
        if ($this->addFormHeader == true) {
            if (!is_null($this->formHeaderText)) {
               $form->addElement('header', '', $this->formHeaderText);
            } else {
               $form->addElement('header', '', $this->_do->tableName());
            }
        }    
    }
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createHiddenField()
     *
     * Returns a QuickForm element for a hidden field.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createHiddenField($fieldName)
    {
        $element =& HTML_QuickForm::createElement('hidden', $fieldName, 
                                                  $this->getFieldLabel($fieldName));   
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createRadioButtons()
     *
     * Returns a QuickForm element for a group of radio buttons.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element group
     * @param array  $options      The list of options to generate the radio buttons for
     * @return array               Array of HTML_QuickForm_element objects.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createRadioButtons($fieldName, $options)
    {
        $element = array();
        foreach($options as $value => $display) {
            $element[] =& HTML_QuickForm::createElement('radio', $fieldName, null, 
                                                        $display, $value);
        }
        return $element;
    }

    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createCheckbox()
     *
     * Returns a QuickForm element for a checkbox.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @param string $text         Text to label the checkbox
     * @param string $value        The value that is submitted when the checkbox is checked
     * @param boolean $checked     Is the checkbox checked? (Default: False)
     * @param boolean $freeze      Is the checkbox frozen? (Default: False)
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createCheckbox($fieldName, $text, $value, $checked = false, $freeze = false)
    {
        $element =& HTML_QuickForm::createElement('checkbox', $fieldName, null, $text);
        $element->updateAttributes(array('value' => $value));
        if ($checked) {
            $element->setChecked(true);
        }
        if ($freeze) {
            $element->freeze();
        }
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createTextField()
     *
     * Returns a QuickForm element for a single-line text field.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createTextField($fieldName)
    {
        $element =& HTML_QuickForm::createElement($this->_getQFType('shorttext'), $fieldName, 
                                                  $this->getFieldLabel($fieldName));
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createIntegerField()
     *
     * Returns a QuickForm element for an integer field.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createIntegerField($fieldName)
    {
        $element =& HTML_QuickForm::createElement($this->_getQFType('integer'), $fieldName, 
                                                  $this->getFieldLabel($fieldName));
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createTextArea()
     *
     * Returns a QuickForm element for a long text field.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createTextArea($fieldName)
    {
        $element =& HTML_QuickForm::createElement($this->_getQFType('longtext'), $fieldName, 
                                                  $this->getFieldLabel($fieldName));
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createSelectBox()
     *
     * Returns a QuickForm element for a selectbox/combobox.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @param array  $options      List of options for populating the selectbox
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createSelectBox($fieldName, $options)
    {
        $element =& HTML_QuickForm::createElement('select', $fieldName, 
                                                  $this->getFieldLabel($fieldName), $options);
        return $element;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createGroup()
     *
     * Takes a form object and a field name and adds an element group to the form.
     * Used in _generateForm().
     *
     * @param object $form         The QuickForm object to add the group to
     * @param string $fieldName    The field name to use for the QuickForm element group
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _createGroup(&$form, $fieldName)
    {
        $form->addGroup(array(), $fieldName, $fieldName, '<br/>');   
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createStaticField()
     *
     * Returns a QuickForm element for displaying static HTML.
     * Used in _generateForm().
     *
     * @param string $fieldName    The field name to use for the QuickForm element
     * @param string $text         The text or HTML code to display in place of this element
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createStaticField($fieldName, $text = null)
    {
        $element =& HTML_QuickForm::createElement('static', $fieldName, $this->getFieldLabel($fieldName), $text);
        return $element;
    }
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_addElementGroupToForm()
     *
     * Adds a group of elements to a form object
     * Used in _generateForm().
     *
     * @param object $form         The QuickForm object to add the group to
     * @param array  $element      Array of QuickForm element objects
     * @param string $fieldName    The field name to use for the QuickForm element group
     * @param string $separator    Some text or HTML snippet used to separate the group entries
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _addElementGroupToForm(&$form, &$element, $fieldName, $separator = '')
    {
        $form->addGroup($element, $fieldName, $this->getFieldLabel($fieldName), $separator, false);
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_addElementToForm()
     *
     * Adds a QuickForm element to a form object
     * Used in _generateForm().
     *
     * @param object $form    The form object to add the element to
     * @param object $element The element object to be added
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _addElementToForm(&$form, $element)
    {
        $form->addElement($element);   
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_setFormElementRequired()
     *
     * Adds a required rule for a specific element to a form
     * Used in _generateForm().
     *
     * @param object $form      The form object to add the rule to
     * @param object $fieldName The name of the required field
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _setFormElementRequired(&$form, $fieldName)
    {
        $form->addRule($key, sprintf($this->requiredRuleMessage, $key), 'required');   
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_addFieldRulesToForm()
     *
     * Adds a set of rules to a form that will apply to a specific element
     * Used in _generateForm().
     *
     * @param object $form      The form object to add the ruleset to
     * @param array  $rules     Array of rule names to be enforced on the element (must be registered QuickForm rules)
     * @param string $fieldName Name of the form element in question
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _addFieldRulesToForm(&$form, $rules, $fieldName)
    {
        foreach ($rules as $rule) {
            if ($rule['rule'] === false) {
                $form->addRule($fieldName, sprintf($this->ruleViolationMessage, $fieldName), $rule['validator']);
            } else {
                $form->addRule($fieldName, sprintf($this->ruleViolationMessage, $fieldName), $rule['validator'], $rule['rule']);
            } // End if
        } // End while
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_freezeFormElements()
     *
     * Freezes a list of form elements (set read-only).
     * Used in _generateForm().
     *
     * @param object $form               The form object in question
     * @param array  $elements_to_freeze List of element names to be frozen
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function _freezeFormElements(&$form, $elements_to_freeze)
    {
        foreach ($elements_to_freeze as $element_to_freeze) {
            if ($form->elementExists($element_to_freeze)) {
                $el =& $form->getElement($element_to_freeze);
                $el->freeze();
            }
        }   
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createSubmitButton()
     *
     * Returns a QuickForm element for a submit button.
     * Used in _generateForm().
     *
     * @return object      The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createSubmitButton()
    {
        $submit =& HTML_QuickForm::createElement('submit', '__submit__', $this->submitText);
        return $submit;
    }
    
    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createDateElement()
     *
     * Returns a QuickForm element for entering date values.
     * Used in _generateForm().
     *
     * @param string $name  The field name to use for the element
     * @return object       The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createDateElement($name) {
        $dateOptions = array('format' => $this->dateElementFormat);
        if (method_exists($this->_do, 'dateoptions')) {
            $dateOptions = array_merge($dateOptions, $this->_do->dateOptions($name));
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('date'), $name, $this->getFieldLabel($name), $dateOptions);
        
        return $element;  
    }

    
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createTimeElement()
     *
     * Returns a QuickForm element for entering time values.
     * Used in _generateForm().
     * Note by Frank: The only reason for this is the difference in timeoptions so it 
     * probably would be better integrated with _createDateElement
     *
     * @param string $name The field name to use for the element
     * @return object      The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createTimeElement($name) {
        $timeOptions = array('format' => $this->timeElementFormat);
        if (method_exists($this->_do, 'timeoptions')) { // Frank: I'm trying to trace this but am unsure of it //
            $timeOptions = array_merge($timeOptions, $this->_do->timeOptions($name));
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('date'), $name, $this->getFieldLabel($name), $timeOptions);
        
        return $element;  
    }

      
    /**
     * DB_DataObject_FormBuilder::_getQFType()
     *
     * Returns the QuickForm element type associated with the given field type,
     * as defined in the elementTypeMap property. If an unknown field type is given,
     * the returned type name will default to 'text'.
     *
     * @access protected
     * @param  string $fieldType   The internal field type
     * @return string              The QuickForm element type name
     */
    function _getQFType($fieldType)
    {
        if (isset($this->elementTypeMap[$fieldType])) {
            return $this->elementTypeMap[$fieldType];
        }
        return 'text';
    }
}

?>

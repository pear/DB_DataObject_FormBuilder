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
     * Array to determine what QuickForm element types are being used for which
     * general field types. If you configure FormBuilder using arrays, the format is:
     * array('nameOfFieldType' => 'QuickForm_Element_name', ...);
     * If configured via .ini file, the format looks like this:
     * elementTypeMap = shorttext:text,date:date,...
     *
     * Allowed field types:
     * <ul><li>shorttext</li>
     * <li>longtext</<li>
     * <li>date</li>
     * <li>integer</li>
     * <li>float</li></ul>
     */
    var $elementTypeMap = array('shorttext' => 'text',
                                'longtext'  => 'textarea',
                                'date'      => 'date',
                                'time'      => 'date',
                                'datetime'  => 'date',
                                'integer'   => 'text',
                                'float'     => 'text',
                                'select'    => 'select',
                                'elementTable' => 'elementTable');

    /**
     * Array of attributes for each element type. See the keys of elementTypeMap
     * for the allowed element types.
     *
     * The key is the element type. The value can be a valid attribute string or
     * an associative array of attributes.
     */
    var $elementTypeAttributes = array();

    /**
     * Array of attributes for each specific field.
     *
     * The key is the field name. The value can be a valid attribute string or
     * an associative array of attributes.
     */
    var $fieldAttributes = array();

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
     * DB_DataObject_FormBuilder_QuickForm::_getQFType()
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

    /**
     * DB_DataObject_FormBuilder_QuickForm::_getAttributes()
     *
     * Returns the attributes to apply to a field based on the field name and
     * element type. The field's attributes take precedence over the element type's.
     *
     * @param string $elementType the internal type of the element
     * @param string $fieldName the name of the field
     * @return array an array of attributes to apply to the element
     */
    function _getAttributes($elementType, $fieldName) {
        if (isset($this->elementTypeAttributes[$elementType])) {
            if (is_string($this->elementTypeAttributes[$elementType])) {
                $this->elementTypeAttributes[$elementType] =
                    HTML_Common::_parseAttributes($this->elementTypeAttributes[$elementType]);
            }
            $attr = $this->elementTypeAttributes[$elementType];
        } else {
            $attr = array();
        }
        if (isset($this->fieldAttributes[$fieldName])) {
            if (is_string($this->fieldAttributes[$fieldName])) {
                $this->fieldAttributes[$fieldName] =
                    HTML_Common::_parseAttributes($this->fieldAttributes[$fieldName]);
            }
            $attr = array_merge($attr, $this->fieldAttributes[$fieldName]);
        }
        return $attr;
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
        $element =& HTML_QuickForm::createElement('hidden',
                                                  $this->getFieldName($fieldName));   
        $attr = $this->_getAttributes('hidden', $fieldName);
        $element->updateAttributes($attr);
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
        $attr = $this->_getAttributes('radio', $fieldName);
        foreach($options as $value => $display) {
            unset($radio);
            $radio =& HTML_QuickForm::createElement('radio',
                                                    $this->getFieldName($fieldName),
                                                    null, 
                                                    $display,
                                                    $value);
            $radio->updateAttributes($attr);
            $element[] =& $radio;
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
    function &_createCheckbox($fieldName, $text = null, $value = null, $label = null, $checked = false, $freeze = false)
    {
        $element =& HTML_QuickForm::createElement('checkbox',
                                                  $this->getFieldName($fieldName),
                                                  $label,
                                                  $text);
        if ($value !== null) {
            $element->updateAttributes(array('value' => $value));
        }
        if ($checked) {
            $element->setChecked(true);
        }
        if ($freeze) {
            $element->freeze();
        }
        $attr = $this->_getAttributes('checkbox', $fieldName);
        $element->updateAttributes($attr);
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
        $element =& HTML_QuickForm::createElement($this->_getQFType('shorttext'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName));
        $attr = $this->_getAttributes('shorttext', $fieldName);
        $element->updateAttributes($attr);
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
        $element =& HTML_QuickForm::createElement($this->_getQFType('integer'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName));
        $attr = $this->_getAttributes('integer', $fieldName);
        $element->updateAttributes($attr);
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
        $element =& HTML_QuickForm::createElement($this->_getQFType('longtext'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName));
        $attr = $this->_getAttributes('longtext', $fieldName);
        $element->updateAttributes($attr);
        return $element;
    }
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createSelectBox()
     *
     * Returns a QuickForm element for a selectbox/combobox.
     * Used in _generateForm().
     *
     * @param string  $fieldName   The field name to use for the QuickForm element
     * @param array   $options     List of options for populating the selectbox
     * @param boolean $multiple    If set to true, the select box will be a multi-select
     * @return object              The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createSelectBox($fieldName, $options, $multiple = false)
    {
        if ($multiple) {
            $element =& HTML_QuickForm::createElement($this->_getQFType('select'),
                                                      $this->getFieldName($fieldName),
                                                      $this->getFieldLabel($fieldName),
                                                      $options,
                                                      array('multiple' => 'multiple'));
        } else {
            $element =& HTML_QuickForm::createElement($this->_getQFType('select'),
                                                      $this->getFieldName($fieldName),
                                                      $this->getFieldLabel($fieldName),
                                                      $options);
        }
        $attr = $this->_getAttributes('select', $fieldName);
        $element->updateAttributes($attr);
        return $element;
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
        $element =& HTML_QuickForm::createElement('static',
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName),
                                                  $text);
        $attr = $this->_getAttributes('static', $fieldName);
        $element->updateAttributes($attr);
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
        $form->addGroup($element,
                        $this->getFieldName($fieldName),
                        $this->getFieldLabel($fieldName),
                        $separator,
                        false);
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
    function _addElementToForm(&$form, &$element)
    {
        $form->addElement($element);   
    }

    /**
     * DB_DataObject_FormBuilder_QuickForm::_addSubmitButtonToForm()
     *
     * @param HTML_QuickForm the form to add the submit button to
     * @param string the name of the submit element to be created
     * @param string the text to be put on the submit button
     */
    function _addSubmitButtonToForm(&$form, $fieldName, $text)
    {
        $element =& $this->_createSubmitButton($fieldName, $text);
        $this->_addElementToForm($form, $element);
    }
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createSubmitButton()
     *
     * Returns a QuickForm element for a submit button.
     * Used in _generateForm().
     *
     * @param  string      the name of the submit button
     * @param  string      the text to put in the button
     * @return object      The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createSubmitButton($fieldName, $text)
    {
        $element =& HTML_QuickForm::createElement('submit', $fieldName, $text);
        $attr = $this->_getAttributes('submit', $fieldName);
        $element->updateAttributes($attr);
        return $element;
    }
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_createDateElement()
     *
     * Returns a QuickForm element for entering date values.
     * Used in _generateForm().
     *
     * @param string $fieldName  The field name to use for the element
     * @return object       The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createDateElement($fieldName) {
        $dateOptions = array('format' => $this->dateElementFormat,
                             'language' => $this->dateFieldLanguage);
        if (method_exists($this->_do, 'dateoptions')) {
            $dateOptions = array_merge($dateOptions, $this->_do->dateOptions($fieldName));
        }
        if (!isset($dateOptions['addEmptyOption']) && in_array($fieldName, $this->selectAddEmpty)) {
            $dateOptions['addEmptyOption'] = true;
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('date'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName),
                                                  $dateOptions);
        $attr = $this->_getAttributes('date', $fieldName);
        $element->updateAttributes($attr);
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
     * @param string $fieldName The field name to use for the element
     * @return object      The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createTimeElement($fieldName) {
        $timeOptions = array('format' => $this->timeElementFormat,
                             'language' => $this->dateFieldLanguage);
        if (method_exists($this->_do, 'timeoptions')) { // Frank: I'm trying to trace this but am unsure of it //
            $timeOptions = array_merge($timeOptions, $this->_do->timeOptions($fieldName));
        }
        if (!isset($timeOptions['addEmptyOption']) && in_array($fieldName, $this->selectAddEmpty)) {
            $timeOptions['addEmptyOption'] = true;
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('time'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName),
                                                  $timeOptions);
        $attr = $this->_getAttributes('time', $fieldName);
        $element->updateAttributes($attr);
        return $element;  
    }

    /**
     * DB_DataObject_FormBuilder_QuickForm::_createDateTimeElement()
     *
     * Returns a QuickForm element for entering date values.
     * Used in _generateForm().
     *
     * @param string $fieldName  The field name to use for the element
     * @return object       The HTML_QuickForm_element object.
     * @access protected
     * @see DB_DataObject_FormBuilder::_generateForm()
     */
    function &_createDateTimeElement($fieldName) {
        $dateOptions = array('format' => $this->dateTimeElementFormat,
                             'language' => $this->dateFieldLanguage);
        if (method_exists($this->_do, 'datetimeoptions')) {
            $dateOptions = array_merge($dateOptions, $this->_do->dateTimeOptions($fieldName));
        }
        if (!isset($dateOptions['addEmptyOption']) && in_array($fieldName, $this->selectAddEmpty)) {
            $dateOptions['addEmptyOption'] = true;
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('datetime'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName),
                                                  $dateOptions);
        $attr = $this->_getAttributes('datetime', $fieldName);
        $element->updateAttributes($attr);
        return $element;  
    }

    /**
     * DB_DataObject_FormBuilder_QuickForm::_addElementTableToForm
     *
     * Adds an elementTable to the form
     *
     * @param HTML_QuickForm $form        the form to add the element to
     * @param string         $fieldName        the name of the element to be added
     * @param array          $columnNames an array of the column names
     * @param array          $rowNames    an array of the row names
     * @param array          $rows        an array of rows, each row being an array of HTML_QuickForm elements
     */
    function _addElementTableToForm(&$form, $fieldName, $columnNames, $rowNames, &$rows) {
        if (!HTML_QuickForm::isTypeRegistered('elementTable')) {
            HTML_QuickForm::registerElementType('elementTable',
                                                'DB/DataObject/FormBuilder/QuickForm/ElementTable.php',
                                                'DB_DataObject_FormBuilder_QuickForm_ElementTable');
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('elementTable'),
                                                  $this->getFieldName($fieldName),
                                                  $this->getFieldLabel($fieldName));
        $element->setColumnNames($columnNames);
        $element->setRowNames($rowNames);
        $element->setRows($rows);
        $attr = $this->_getAttributes('elementTable', $fieldName);
        $element->updateAttributes($attr);
        $this->_addElementToForm($form, $element);
    }
    
    /**
     * DB_DataObject_FormBuilder_QuickForm::_setFormDefaults()
     *
     * @param HTML_QuickForm the form to set the defaults on
     * @param array Assoc array of default values (@see HTML_QuickForm::setDefaults)
     */    
    function _setFormDefaults(&$form, $defaults)
    {
        $form->setDefaults($defaults);
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
        $this->_addFieldRulesToForm($form,
                                    array(array('validator' => 'required',
                                                'rule' => false,
                                                'message' => $this->requiredRuleMessage)),
                                    $fieldName);
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
        $fieldLabel = $this->getFieldLabel($fieldName);
        $ruleSide = $this->clientRules ? 'client' : 'server';
        foreach ($rules as $rule) {
            if ($rule['rule'] === false) {
                $form->addRule($this->getFieldName($fieldName),
                               sprintf($rule['message'], $fieldLabel),
                               $rule['validator'],
                               '', 
                               $ruleSide);
            } else {
                $form->addRule($this->getFieldName($fieldName),
                               sprintf($rule['message'], $fieldLabel),
                               $rule['validator'],
                               $rule['rule'],
                               $ruleSide);
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
    function _freezeFormElements(&$form, $elementsToFreeze)
    {
        foreach ($elementsToFreeze as $elementToFreeze) {
            $elementToFreeze = $this->getFieldName($elementToFreeze);
            if ($form->elementExists($elementToFreeze)) {
                $el =& $form->getElement($elementToFreeze);
                $el->freeze();
            }
        }   
    }
}

?>
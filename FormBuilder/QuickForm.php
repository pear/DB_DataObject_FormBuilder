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
     * DB_DataObject_FormBuilder::_generateForm()
     *
     * Builds a simple HTML form for the current DataObject. Internal function, called by
     * the public getForm() method. You can override this in child classes if needed, but
     * it's also possible to leave this as it is and just override the getForm() method
     * to simply fine-tune the auto-generated form object (i.e. add/remove elements, alter
     * options, add/remove rules etc.).
     * If a key with the same name as the current field is found in the fb_preDefElements
     * property, the QuickForm element object contained in that array will be used instead
     * of auto-generating a new one. This allows for complete step-by-step customizing of
     * your forms.
     *
     * Note for date fields: HTML_QuickForm allows passing of an options array to the
     * HTML_QuickForm_date element. You can define your own options array for date elements
     * in your DataObject-derived classes by defining a method "dateOptions($fieldName)".
     * FormBuilder will call that method whenever it encounters a date field and expects to
     * get back a valid options array.
     *
     * @param string $action   The form action. Optional. If set to false (default), PHP_SELF is used.
     * @param string $target   The window target of the form. Optional. Defaults to '_self'.
     * @param string $formName The name of the form, will be used in "id" and "name" attributes. If set to false (default), the class name is used
     * @param string $method   The submit method. Defaults to 'post'.
     * @return object
     * @access protected
     * @author Markus Wolff <mw21st@php.net>
     * @author Fabien Franzen <atelierfabien@home.nl>
     */    
    function &_generateForm($action = false, $target = '_self', $formName = false, $method = 'post')
    {
        if ($formName === false) {
            $formName = get_class($this->_do);
        }
        if ($action === false) {
            $action = $_SERVER['PHP_SELF'];   
        }

        // If there is an existing QuickForm object, and the form object should not just be
        // appended, use that one. If not, make a new one.
        if (is_a($this->_form, 'html_quickform') && $this->_appendForm == false) {
            $form =& $this->_form;
        } else {
            $form =& new HTML_QuickForm($formName, $method, $action, $target);
        }

        // Initialize array with default values
        $formValues = $this->_do->toArray();

        // Add a header to the form - set addFormHeader property to false to prevent this
        if ($this->addFormHeader == true) {
            if (!is_null($this->formHeaderText)) {
               $form->addElement('header', '', $this->formHeaderText);
            } else {
               $form->addElement('header', '', $this->_do->tableName());
            }
        }

        // Go through all table fields and create appropriate form elements
        $keys = $this->_do->keys();

        // Reorder elements if requested
        $elements = $this->_reorderElements();
        if ($elements == false) { //no sorting necessary
            $elements = $this->_getFieldsToRender();
        }

        //GROUPING
        if (isset($this->preDefGroups)) {
            $groupelements = array_keys((array)$this->preDefGroups);
        }
        
        //get elements to freeze
        $user_editable_fields = $this->_getUserEditableFields();
        if (is_array($user_editable_fields)) {
            $elements_to_freeze = array_diff(array_keys($elements), $user_editable_fields);
        } else {
            $elements_to_freeze = null;
        }

        $links = $this->_do->links();
        foreach ($elements as $key => $type) {
            // Check if current field is primary key. And primary key hiding is on. If so, make hidden field
            if (in_array($key, $keys) && $this->hidePrimaryKey === true) {
                $element =& HTML_QuickForm::createElement('hidden', $key, $this->getFieldLabel($key));
            } else {
                unset($element);
                // Try to determine field types depending on object properties
                if (in_array($key, $this->dateFields)) {
                    $type = DB_DATAOBJECT_DATE;
                //FF ## ADDED ## FF//
                } elseif (in_array($key, $this->timeFields)) {
                    $type = DB_DATAOBJECT_TIME;
                } elseif (in_array($key, $this->textFields)) {
                    $type = DB_DATAOBJECT_TXT;
                } elseif (in_array($key, $this->enumFields)) {
                    $type = DB_DATAOBJECT_FORMBUILDER_ENUM;
                }
                if (isset($this->preDefElements[$key]) && is_object($this->preDefElements[$key])) {
                    // Use predefined form field
                    $element =& $this->preDefElements[$key];
                } elseif (is_array($links) && isset($links[$key])) {
                    $opt = $this->getSelectOptions($key);
                    if (isset($this->linkElementTypes[$key]) && $this->linkElementTypes[$key] == 'radio') {
                        $element = array();
                        foreach($opt as $value => $display) {
                            $element[] =& HTML_QuickForm::createElement('radio', $key, null, $display, $value);
                        }
                    } else {
                        $element =& HTML_QuickForm::createElement('select', $key, $this->getFieldLabel($key), $opt);
                    }
                    unset($opt);
                }

                // No predefined object available, auto-generate new one
                $elValidator = false;
                $elValidRule = false;

                // Auto-detect field types depending on field's database type
                switch (true) {
                case ($type & DB_DATAOBJECT_INT):
                    if (!isset($element)) {
                        $element =& HTML_QuickForm::createElement($this->_getQFType('integer'), $key, $this->getFieldLabel($key));
                    }
                    $elValidator = 'numeric';
                    break;
                case ($type & DB_DATAOBJECT_DATE): // TODO
                    $this->debug("DATE CONVERSION using callback for element $key ({$this->_do->$key})!", "FormBuilder");
                    $formValues[$key] = call_user_func($this->dateFromDatabaseCallback, $this->_do->$key);
                    if (!isset($element)) {
                        $element =& $this->_createDateElement($key);
                    }
                    break;
                case ($type & DB_DATAOBJECT_DATE & DB_DATAOBJECT_TIME):
                    $this->debug('DATE & TIME CONVERSION using callback for element '.$key.' ('.$this->_do->$key.')!', 'FormBuilder');
                    $formValues[$key] = call_user_func($this->dateFromDatabaseCallback, $this->_do->$key);
                    if (!isset($element)) {
                        $element =& $this->_createDateElement($key);  
                    }
                    break;  
                //FF ## MODIFIED/ADDED ## FF//
                case ($type & DB_DATAOBJECT_TIME):
                    $this->debug("TIME CONVERSION using callback for element $key ({$this->_do->$key})!", "FormBuilder");
                    $formValues[$key] = call_user_func($this->dateFromDatabaseCallback, $this->_do->$key);
                    if (!isset($element)) {
                        $element =& $this->_createTimeElement($key);
                    }
                    break;
                case ($type & DB_DATAOBJECT_BOOL): // TODO  
                case ($type & DB_DATAOBJECT_TXT):
                    if (!isset($element)) {
                        $element =& HTML_QuickForm::createElement($this->_getQFType('longtext'), $key, $this->getFieldLabel($key));
                    }
                    break;
                case ($type & DB_DATAOBJECT_STR):
                    // If field content contains linebreaks, make textarea - otherwise, standard textbox
                    if (!empty($this->_do->$key) && strstr($this->_do->$key, "\n")) {
                        $element =& HTML_QuickForm::createElement($this->_getQFType('longtext'), $key, $this->getFieldLabel($key));
                    } elseif (!isset($element)) {
                        $element =& HTML_QuickForm::createElement($this->_getQFType('shorttext'), $key, $this->getFieldLabel($key));
                    }
                    break;
                case ($type & DB_DATAOBJECT_FORMBUILDER_CROSSLINK):
                    unset($element);
                    $form->addGroup(array(), $key, $key, '<br/>');
                    break;
                case ($type & DB_DATAOBJECT_FORMBUILDER_TRIPLELINK):
                    unset($element);
                    $element =& HTML_QuickForm::createElement('static', $key, $key);
                    break;
                case ($type & DB_DATAOBJECT_FORMBUILDER_ENUM):
                    if (!isset($element)) {
                        $db = $this->_do->getDatabaseConnection();
                        $option = $db->getRow('SHOW COLUMNS FROM '.$this->_do->__table.' LIKE '.$db->quoteSmart($key), DB_FETCHMODE_ASSOC);
                        $option = substr($option['Type'], strpos($option['Type'], '(') + 1);
                        $option = substr($option, 0, strrpos($option, ')') - strlen($option));
                        $split = explode(',', $option);
                        $options = array();
                        $option = '';
                        for ($i = 0; $i < sizeof($split); ++$i) {
                            $option .= $split[$i];
                            if (substr_count($option, "'") % 2 == 0) {
                                $option = trim(trim($option), "'");
                                $options[$option] = $option;
                                $option = '';
                            }
                        }
                        $element = array();
                        if (isset($this->linkElementTypes[$key]) && $this->linkElementTypes[$key] == 'radio') {
                            foreach ($options as $option) {
                                $element[] = HTML_QuickForm::createElement('radio', $key, null, $option, $option);
                            }
                        } else {
                            $element = HTML_QuickForm::createElement('select', $key, $this->getFieldLabel($key), $options);
                        }
                    }
                    break;
                default:
                    if (!isset($element)) {
                        $element =& HTML_QuickForm::createElement('text', $key, $this->getFieldLabel($key));
                    }
                } // End switch
                //} // End else                

                if ($elValidator !== false) {
                    $rules[$key][] = array('validator' => $elValidator, 'rule' => $elValidRule);
                } // End if
                                        
            } // End else
                    
            //GROUP OR ELEMENT ADDITION
            if (isset($groupelements) && in_array($key, $groupelements)) {
                $group = $this->preDefGroups[$key];
                $groups[$group][] = $element;
            } elseif (isset($element)) {
                if (is_array($element)) {
                    $form->addGroup($element, $key, $this->getFieldLabel($key), '', false);
                } else {
                    $form->addElement($element);
                }
            } // End if
            
            //ADD REQURED RULE FOR NOT_NULL FIELDS
            if ((!in_array($key, $keys) || $this->hidePrimaryKey === false)
                && ($type & DB_DATAOBJECT_NOTNULL)
                && !in_array($key, $elements_to_freeze)) {
                $form->addRule($key, sprintf($this->requiredRuleMessage, $key), 'required');
            }

            //VALIDATION RULES
            if (isset($rules[$key])) {
                foreach ($rules[$key] as $rule) {
                    if ($rule['rule'] === false) {
                        $form->addRule($key, sprintf($this->ruleViolationMessage, $key), $rule['validator']);
                    } else {
                        $form->addRule($key, sprintf($this->ruleViolationMessage, $key), $rule['validator'], $rule['rule']);
                    } // End if
                } // End while
            } // End if     
        } // End foreach

        // Freeze fields that are not to be edited by the user
        foreach ($elements_to_freeze as $element_to_freeze) {
            if ($form->elementExists($element_to_freeze)) {
                $el =& $form->getElement($element_to_freeze);
                $el->freeze();
            }
        }
        
        //GROUP SUBMIT
        $flag = true;
        if (isset($groupelements) && in_array('__submit__', $groupelements)) {
            $group = $this->preDefGroups['__submit__'];
            if (count($groups[$group]) > 1) {
                $groups[$group][] =& HTML_QuickForm::createElement('submit', '__submit__', 'Submit');
                $flag = false;
            } else {
                $flag = true;
            }   
        }
        
        // generate tripleLink stuff
        // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
        if (isset($this->tripleLinks) && is_array($this->tripleLinks)) {
            // primary key detection taken from getSelectOptions() so it doesn't allow
            // the use of multiple keys... this should be improved in the future if possible imho..
            if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }
            if (empty($pk)) {
                return PEAR::raiseError('A primary key must exist in the base table when using tripleLinks.');
            }
            foreach ($this->tripleLinks as $tripleLink) {
                $elName  = '__tripleLink_' . $tripleLink['table'];
                if ($form->elementExists($elName)) {
                    $freeze = array_search('__tripleLink_' . $tripleLink['table'], $elements_to_freeze);
                    $do = DB_DataObject::factory($tripleLink['table']);
                    if (PEAR::isError($do)) {
                        die($do->getMessage());
                    }

                    $links = $do->links();

                    if (isset($tripleLink['fromField'])) {
                        $fromField = $tripleLink['fromField'];
                    } else {
                        unset($fromField);
                    }
                    if (isset($tripleLink['toField1'])) {
                        $toField1 = $tripleLink['toField1'];
                    } else {
                        unset($toField1);
                    }
                    if (isset($tripleLink['toField2'])) {
                        $toField2 = $tripleLink['toField2'];
                    } else {
                        unset($toField2);
                    }
                    if (!isset($toField2) || !isset($toField1) || !isset($fromField)) {
                        foreach ($links as $field => $link) {
                            list($linkTable, $linkField) = explode(':', $link);
                            if (!isset($fromField) && $linkTable == $this->_do->__table) {
                                $fromField = $field;
                            } elseif (!isset($toField1) && $linkField != $fromField) {
                                $toField1 = $field;
                            } elseif (!isset($toField2) && $linkField != $fromField && $linkField != $toField1) {
                                $toField2 = $field;
                            }
                        }
                    }

                    list($linkedtable1, $linkedfield1) = explode(':', $links[$toField1]);
                    list($linkedtable2, $linkedfield2) = explode(':', $links[$toField2]);

                    $all_options1 = $this->_getSelectOptions($linkedtable1);
                    $all_options2 = $this->_getSelectOptions($linkedtable2);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $do->$fromField = $this->_do->$pk;
                        if ($do->find() > 0) {
                            while ($do->fetch()) {
                                $selected_options[$do->$toField1][] = $do->$toField2;
                            }
                        }
                    }

                    include_once ('HTML/Table.php');
                    $table = new HTML_Table();
                    $table->setAutoGrow(true);
                    $table->setAutoFill('');
                    $row = 0;
                    $col = 0;
                    foreach ($all_options2 as $key2=>$value2) {
                        $col++;
                        $table->setCellContents($row, $col, $value2);
                        $table->setCellAttributes($row, $col, array('style' => 'text-align: center'));
                    }
                    foreach ($all_options1 as $key1=>$value1) {
                        $row++;
                        $col = 0;
                        $table->setCellContents($row, $col, $value1);
                        foreach ($all_options2 as $key2=>$value2) {
                            $col++;
                            $element = HTML_QuickForm::createElement('checkbox', '__tripleLink_' . $tripleLink['table'] . '[' . $key1 . '][]', null, null);
                            $element->updateAttributes(array('value' => $key2));
                            if ($freeze) {
                                $element->freeze();
                            }
                            if (is_array($selected_options[$key1])) {
                                if (in_array($key2, $selected_options[$key1])) {
                                    $element->setChecked(true);
                                }
                            }
                            $table->setCellContents($row, $col, $element->toHTML());
                            $table->setCellAttributes($row, $col, array('style' => 'text-align: center'));
                        }
                    }
                    $hrAttrs = array('bgcolor' => 'lightgrey');

                    $table->setRowAttributes(0, $hrAttrs, true);
                    $table->setColAttributes(0, $hrAttrs);
                    $elLabel = $this->getFieldLabel($elName);
                    $linkElement =& $form->getElement($elName);
                    $linkElement->setLabel($elLabel);
                    $linkElement->setValue($table->toHTML());
                }
            }
        }

        // generate crossLink stuff
        // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
        if (isset($this->crossLinks) && is_array($this->crossLinks)) {
            // primary key detection taken from getSelectOptions() so it doesn't allow
            // the use of multiple keys... this should be improved in the future if possible imho..
            if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }
            if (empty($pk)) {
                return PEAR::raiseError('A primary key must exist in the base table when using crossLinks.');
            }
            foreach ($this->crossLinks as $crossLink) {
                $groupName  = '__crossLink_' . $crossLink['table'];
                if ($form->elementExists($groupName)) {
                    $linkGroup =& $form->getElement($groupName);
                    $do = DB_DataObject::factory($crossLink['table']);
                    if (PEAR::isError($do)) {
                        die($do->getMessage());
                    }

                    $links = $do->links();

                    if (isset($crossLink['fromField'])) {
                        $fromField = $crossLink['fromField'];
                    } else {
                        unset($fromField);
                    }
                    if (isset($crossLink['toField'])) {
                        $toField = $crossLink['toField'];
                    } else {
                        unset($toField);
                    }
                    if (!isset($toField) || !isset($fromField)) {
                        foreach ($links as $field => $link) {
                            list($linkTable, $linkField) = explode(':', $link);
                            if (!isset($fromField) && $linkTable == $this->_do->__table) {
                                $fromField = $field;
                            } elseif (!isset($toField) && $linkField != $fromField) {
                                $toField = $field;
                            }
                        }
                    }

                    list($linkedtable, $linkedfield) = explode(':', $links[$toField]);
                    $all_options      = $this->_getSelectOptions($linkedtable);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $do->$fromField = $this->_do->$pk;
                        if ($do->find() > 0) {
                            while ($do->fetch()) {
                                $selected_options[] = $do->$toField;
                            }
                        }
                    }

                    /*if (isset($crossLink['type']) && $crossLink['type'] == 'select') {
                        // ***X*** generate a <select>
                        $caption = $this->getFieldLabel($groupName);
                        $element =& HTML_QuickForm::createElement('select', $groupName, $caption, $all_options, array('multiple' => 'multiple'));
                        $form->addElement($element);
                        $formValues['__crossLink_' . $crossLink['table']] = $selected_options; // set defaults later
                    
                    // ***X*** generate checkboxes
                    } else {*/
                    $grp = array();
                    foreach ($all_options as $key=>$value) {
                        $element = HTML_QuickForm::createElement('checkbox', '', null, $value);
                        $element->updateAttributes(array('value' => $key));
                        if (in_array($key, $selected_options)) {
                            $element->setChecked(true);
                        }
                        $grp[] = $element;
                    }
                    $groupLabel = $this->getFieldLabel($groupName);
                    $linkGroup->setLabel($groupLabel);
                    $linkGroup->setElements($grp);
                    //}
                }
            }
        }

        //GROUPING  
        if (isset($groups) && is_array($groups)) { //apply grouping
            while (list($grp, $elements) = each($groups)) {
                if (count($elements) == 1) {  
                    $form->addElement($elements);
                } elseif (count($elements) > 1) {
                    $form->addGroup($elements, $grp, $grp, '&nbsp;');
                }
            }       
        }

        //ELEMENT SUBMIT
        if ($flag == true && $this->createSubmit == true) {
            $form->addElement('submit', '__submit__', $this->submitText);
        }
        
        //APPEND EXISTING FORM ELEMENTS
        if (is_a($this->_form, 'html_quickform') && $this->_appendForm == true) {
            // There somehow needs to be a new method in QuickForm that allows to fetch
            // a list of all element names currently registered in a form. Otherwise, there
            // will be need for some really nasty workarounds once QuickForm adopts PHP5's
            // new encapsulation features.
            reset($this->_form->_elements);
            while (list($elNum, $element) = each($this->_form->_elements)) {
                $form->addElement($element);
            }
        }

        // Assign default values to the form
        $form->setDefaults($formValues);        
        return $form;
    }

    function &_createDateElement($name) {
        $dateOptions = array('format' => $this->dateElementFormat);
        if (method_exists($this->_do, 'dateoptions')) {
            $dateOptions = array_merge($dateOptions, $this->_do->dateOptions($name));
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('date'), $name, $this->getFieldLabel($name), $dateOptions);
        
        return $element;  
    }

    //FF ## ADDED ## FF//
    function &_createTimeElement($name) { //Frank: the only reason for this is the difference in timeoptions so it probably would be better integrated with _createDateElement //
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
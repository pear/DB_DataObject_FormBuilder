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
* This class adds some nice utility methods to the DataObject class
* to speed up prototyping new applications - like auto-generating fully
* functional forms using HTML_QuickForm.
*
* The following new options to the DataObject.ini file can be used to configure
* the form-generating behaviour of this class:
* <ul><li>select_display_field:
* The field to be used for displaying the options of an auto-generated
* select element. Can be overridden individually by a similarly-named
* public class property.</li>
* <li>select_order_field:
* The field to be used for sorting the options of an auto-generated
* select element. Can be overridden individually by a similarly-named
* public class property.</li>
* <li>db_date_format:
* This is for the future support of string date formats other than ISO, but
* currently, that's the only supported one. Set to 1 for ISO, other values
* may be available later on.</li>
* <li>date_element_format:
* A format string that represents the display settings for QuickForm date elements.
* Example: "d-m-Y". See QuickForm documentation for details on format strings.
* Legal letters to use in the format string that work with FormBuilder are:
* d,m,Y,H,i,s</li>
* <li>hide_primary_key:
* By default, hidden fields are generated for the primary key of a DataObject.
* This behaviour can be deactivated by setting this option to 0.</li>
* <li>createSubmit:
* If set to 0, no submit button will be created for your forms. Useful when
* used together with QuickForm_Controller when you already have submit buttons
* for next/previous page. By default, a button is being generated.</li>
* <li>submitText:
* The caption of the submit button, if created.</li>
* <li>dateFieldLanguage:
* The language to be used in date fields (see HTML_QuickForm documentation on
* the date element for more details). This option is the only one that cannot be
* overridden in one of your classes.</li></ul>
* All the settings for FormBuilder must be in a section [DB_DataObject_FormBuilder]
* within the DataObject.ini file (or however you've named it).
* If you stuck to the DB_DataObject example in the doc, you'll read in your
* config like this:
* <code>
* $config = parse_ini_file('DataObject.ini',TRUE);
* foreach($config as $class=>$values) {
*     $options = &PEAR::getStaticProperty($class,'options');
*     $options = $values;
* }
* </code>
* Unfortunately, DataObject will overwrite FormBuilder's settings when first instantiated,
* so you'll have to add another line after that:
* <code>
* $_DB_DATAOBJECT_FORMBUILDER['CONFIG'] = $config['DB_DataObject_FormBuilder'];
* </code>
* Now you're ready to go!
*
* There are some more settings that can be set individually by altering
* some special properties of your DataObject-derived classes.
* These special properties are as follows:
* <ul><li>preDefElements:
* Array of user-defined QuickForm elements that will be used
* for the field matching the array key. If no match is found,
* the element for that field will be auto-generated.
* Make your element objects either in the constructor or in
* the getForm() method, before the _generateForm() method is
* called. Use HTML_QuickForm::createElement() to do this.</li>
* <li>preDefOrder:
* Indexed array of element names. If defined, this will determine the order
* in which the form elements are being created. This is useful if you're using
* QuickForm's default renderer or dynamic templates and the order of the fields
* in the database doesn?t match your needs.</li>
* <li>fieldLabels:
* Array of field labels. The key of the element represents the field name.
* Use this if you want to keep the auto-generated elements, but still define
* your own labels for them.</li>
* <li>dateFields:
* A simple array of field names indicating which of the fields in a particular table/class
* are actually to be treated date fields.
* This is an unfortunate workaround that is neccessary because the DataObject
* generator script does not make a difference between any other datatypes than
* string and integer. When it does, this can be dropped.</li>
* <li>textFields:
* A simple array of field names indicating which of the fields in a particular table/class
* are actually to be treated as textareas.
* This is an unfortunate workaround that is neccessary because the DataObject
* generator script does not make a difference between any other datatypes than
* string and integer. When it does, this can be dropped.</li></ul>
*
* Note for PHP5-users: These properties have to be public! In general, you can
* override all settings from the .ini file by setting similarly-named properties
* in your DataObject classes.
*
* <b>Most basic usage:</b>
* <code>
* $do =& new MyDataObject();
* // Insert "$do->get($some_id);" here to edit an existing object instead of making a new one
* $fg =& DB_DataObject_FormBuilder::create($do);
* $form =& $fg->getForm();
* if ($form->validate()) {
*     $form->process(array(&$fg,'processForm'), false);
*     $form->freeze();
* }
* $form->display();
* </code>
*
* For more information on how to use the DB_DataObject or HTML_QuickForm packages
* themselves, please see the excellent documentation on http://pear.php.net/.
*
* @package  DB_DataObject_FormBuilder
* @author   Markus Wolff <mw21st@php.net>
* @version  $Id$
*/


// Import requirements
require_once('DB/DataObject.php');
require_once('HTML/QuickForm.php');

// Constants used for forceQueryType()
define('DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT',    0);
define('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT',   1);
define('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE',   2);
define('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCENOACTION', 3);

// Constants used for cross/triple links
define('DB_DATAOBJECT_FORMBUILDER_CROSSLINK', 1048576);
define('DB_DATAOBJECT_FORMBUILDER_TRIPLELINK', 2097152);

class DB_DataObject_FormBuilder
{
    /**
     * Add a header to the form - if set to true, the form will
     * have a header element as the first element in the form.
     *
     * @access public
     * @see form_header_text
     */
    var $add_form_header = true;

    /**
     * Text for the form header. If not set, the name of the database
     * table this form represents will be used.
     *
     * @access public
     * @see add_form_header
     */
    var $form_header_text = null;

    /**
     * Text that is displayed as an error message if a validation rule
     * is violated by the user's input. Use %s to insert the field name.
     *
     * @access public
     * @see required_rule_message
     */
    var $rule_violation_message = '%s: The value you have entered is not valid.';
    
    /**
     * Text that is displayed as an error message if a required field is
     * left empty. Use %s to insert the field name.
     *
     * @access public
     * @see rule_violation_message
     */
    var $required_rule_message = 'The field %s is required.';

    /**
     * If you want to use the generator on an existing form object, pass it
     * to the factory method within the options array, element name: 'form'
     * (who would have guessed?)
     *
     * @access protected
     * @see DB_DataObject_Formbuilder()
     */
    var $_form = false;

    /**
     * If set to TRUE, the current DataObject's validate method is being called
     * before the form data is processed. If errors occur, no insert/update operation
     * will be made on the database. Use getValidationErrors() to retrieve the reasons
     * for a failure.
     * Defaults to FALSE.
     *
     * @access public
     */
    var $validateOnProcess = false;

    /**
     * Contains the last validation errors, if validation checking is enabled.
     *
     * @access protected
     */
    var $_validationErrors = false;

    /**
     * Used to determine which action to perform with the submitted data in processForm()
     *
     * @access protected
     */
    var $_queryType = DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT;
    
    /**
     * If false, FormBuilder will use the form object from $_form as a basis for the new
     * form: It will just add elements to the existing form object, not generate a new one.
     * If true, FormBuilder will generate a new form object, create all elements as needed for
     * the given DataObject, then strip the elements from the exiting form object in $_form
     * and add it to the newly generated form object.
     *
     * @access protected
     */
    var $_appendForm = false;
    
    /**
     * The language used in date fields. See documentation of HTML_Quickform's
     * date element for more information.
     *
     * @access protected
     * @see HTML_QuickForm_date
     */
    var $_dateFieldLanguage = 'en';
    
    /**
     * Callback method to convert a date from the format it is stored
     * in the database to the format used by the QuickForm element that
     * handles date values. Must have a format usable with call_user_func().
     *
     * @access protected
     */
    var $_dateFromDatabaseCallback = array('DB_DataObject_FormBuilder','_date2array');
    
    /**
     * Callback method to convert a date from the format used by the QuickForm
     * element that handles date values to the format the database can store it in. 
     * Must have a format usable with call_user_func().
     *
     * @access protected
     */
    var $_dateToDatabaseCallback = array('DB_DataObject_FormBuilder','_array2date');
    
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
     *
     * @access protected
     */
    var $_elementTypeMap = array('shorttext' => 'text',
                                 'longtext'  => 'textarea',
                                 'date'      => 'date',
                                 'integer'   => 'text',
                                 'float'     => 'text');


    /**
     * DB_DataObject_FormBuilder::create()
     *
     * Factory method. Although not really needed at the moment, it is the recommended
     * method to make a new object instance. Benefits: Checks the passed parameters and
     * returns a PEAR_Error object in case something is wrong. Also, it will make
     * your code forward-compatible to future versions of this class, which might include
     * other types or forms, resulting in this being a stripped-down base class that
     * returns a specialized class for the desired purpose (i.e. for generating GTK
     * form elements for use with PHP-GTK, WML forms for WAP...).
     *
     * Options can be:
     * - 'rule_violation_message' : See description of similarly-named class property
     * - 'required_rule_message' : See description of similarly-named class property
     * - 'add_form_header' : See description of similarly-named class property
     * - 'form_header_text' : See description of similarly-named class property
     *
     * @param object $do      The DB_DataObject-derived object for which a form shall be built
     * @param array $options  An optional associative array of options.
     * @access public
     * @returns object        DB_DataObject_FormBuilder or PEAR_Error object
     */
    function &create(&$do, $options = false)
    {
        if (is_a($do, 'db_dataobject')) {
            $obj = &new DB_DataObject_FormBuilder($do, $options);
            return $obj;    
        }

        $err =& PEAR::raiseError('DB_DataObject_FormBuilder::create(): Object does not extend DB_DataObject.',
                               DB_DATAOBJECT_FORMBUILDER_ERROR_WRONGCLASS);
        return $err;
    }


    /**
     * DB_DataObject_FormBuilder::DB_DataObject_FormBuilder()
     *
     * The class constructor.
     *
     * @param object $do      The DB_DataObject-derived object for which a form shall be built
     * @param array $options  An optional associative array of options.
     * @access public
     */
    function DB_DataObject_FormBuilder(&$do, $options=false)
    {
        global $_DB_DATAOBJECT_FORMBUILDER;
        // Set default callbacks first!
        $this->_dateToDatabaseCallback = array($this,'_array2date');
        $this->_dateFromDatabaseCallback = array($this,'_date2array');
        
        // Read in config
        if (is_array($options)) {
            reset($options);
            while (list($key, $value) = each($options)) {
                if (isset($this->$key)) {
                    $this->$key = $value;
                }
            }
        }
        
        // Read date conversion callbacks from configuration array
        if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateToDatabaseCallback'])) {
            $this->_dateToDatabaseCallback = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateToDatabaseCallback'];
        }
        if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateFromDatabaseCallback'])) {
            $this->_dateFromDatabaseCallback = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateFromDatabaseCallback'];
        }
        
        // Default language settings for all date fields
        if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateFieldLanguage'])) {
            $this->_dateFieldLanguage = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['dateFieldLanguage'];
        }
       // Default mappings from global field types to QuickForm element types
        if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['elementTypeMap'])) {
            if (is_string($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['elementTypeMap'])) {
                // ...must have been defined in the .ini file
                foreach (explode(',',$_DB_DATAOBJECT_FORMBUILDER['CONFIG']['elementTypeMap']) as $mapping) {
                    $map = explode(':',$mapping);
                    $this->_elementTypeMap[$map[0]] = $map[1];   
                }
            } elseif (is_array($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['elementTypeMap'])) {
                foreach ($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['elementTypeMap'] as $key=>$value) {
                    $this->_elementTypeMap[$key] = $value;   
                }
            }
        }
        $this->_do = &$do;
        $this->_loadConfig();
    }

    /**
     * DB_DataObject_FormBuilder::_loadConfig()
     *
     * Loads ini file for formBuilder options for the database used
     *
     * @access private
     */
    function _loadConfig() {
        if(!isset($GLOBALS['_DB_DATAOBJECT_FORMBUILDER']['INI'])) {
            if(!$this->_do->database()) {
                $this->_do->keys();
            }
            $formBuilderIni = $GLOBALS['_DB_DATAOBJECT']['CONFIG']['schema_location'].'/'.$this->_do->database().'.formBuilder.ini';
            if(file_exists($formBuilderIni)) {
                $GLOBALS['_DB_DATAOBJECT_FORMBUILDER']['INI'][$this->_do->database()] = parse_ini_file($formBuilderIni, true);
            }
        }
    }


    /**
     * DB_DataObject_FormBuilder::_generateForm()
     *
     * Builds a simple HTML form for the current DataObject. Internal function, called by
     * the public getForm() method. You can override this in child classes if needed, but
     * it's also possible to leave this as it is and just override the getForm() method
     * to simply fine-tune the auto-generated form object (i.e. add/remove elements, alter
     * options, add/remove rules etc.).
     * If a key with the same name as the current field is found in the preDefElements
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
    function &_generateForm($action=false, $target='_self', $formName=false, $method='post')
    {
        global $_DB_DATAOBJECT_FORMBUILDER;

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

        // Add a header to the form - set _add_form_header property to false to prevent this
        if ($this->add_form_header == true) {
            if (!is_null($this->form_header_text)) {
               $form->addElement('header', '', $this->form_header_text);
            } else {
               $form->addElement('header', '', $this->_do->tableName());
            }
        }

        // Go through all table fields and create appropriate form elements
        $keys = $this->_do->keys();

        // Reorder elements if requested
        $elements = $this->_reorderElements();
        if($elements == false) { //no sorting necessary
            $elements = $this->_getFieldsToRender();
        }

        //GROUPING
        if (isset($this->_do->preDefGroups)) {
            $groupelements = array_keys((array)$this->_do->preDefGroups);
        }
        
        // Hiding fields for primary keys
        $hidePrimary = true;
        if ((isset($this->_do->hide_primary_key) && $this->_do->hide_primary_key === false) ||
            (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['hide_primary_key']) && $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['hide_primary_key'] == 0)
           )
        {
            $hidePrimary = false;
        }

        foreach ($elements as $key => $type) {
            // Check if current field is primary key. And primary key hiding is on. If so, make hidden field
            if (in_array($key, $keys) && $hidePrimary === true) {
                $element =& HTML_QuickForm::createElement('hidden', $key, $this->getFieldLabel($key));
            } else {
                if (isset($this->_do->preDefElements[$key]) && is_object($this->_do->preDefElements[$key])) {
                    // Use predefined form field
                    $element =& $this->_do->preDefElements[$key];
                } else {
                    // No predefined object available, auto-generate new one
                    $elValidator = false;
                    $elValidRule = false;
                    // Try to determine field types depending on object properties
                    if (isset($this->_do->dateFields) &&
                        is_array($this->_do->dateFields) &&
                        in_array($key,$this->_do->dateFields)) {
                        $element =& $this->_createDateElement($key);
                        /*$dateOptions = array('format' => $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['date_element_format']);
                        if (method_exists($this->_do, 'dateoptions')) {
                            $dateOptions = array_merge($dateOptions, $this->_do->dateOptions($key));
                        }
                        $element =& HTML_QuickForm::createElement($this->_getQFType('date'), $key, $this->getFieldLabel($key), $dateOptions);
                        
                        // Convert date from database into a format usable with the date element (default: array)
                        if ($this->_dateFromDatabaseCallback != false && function_exists($this->_dateFromDatabaseCallback)) {
                            $this->debug("DATE CONVERSION using callback for element $key ({$this->_do->$key})!", "FormBuilder");
                            $formValues[$key] = call_user_func($this->_dateFromDatabaseCallback, $this->_do->$key);
                        }*/
                    } elseif (isset($this->_do->textFields) && is_array($this->_do->textFields) &&
                              in_array($key,$this->_do->textFields)) {
                        $element =& HTML_QuickForm::createElement($this->_getQFType('longtext'), $key, $this->getFieldLabel($key));
                    } else {
                        $links = $this->_do->links();
                        if (is_array($links) && isset($links[$key])) {
                            $opt = $this->getSelectOptions($key);
                            $element =& HTML_QuickForm::createElement('select', $key, $this->getFieldLabel($key), $opt);
                            unset($opt);
                        } else {
                            unset($element);
                        }
                        unset($links);

                        // Auto-detect field types depending on field's database type
                        switch (true) {
                            case ($type & DB_DATAOBJECT_INT):
                                if (!isset($element)) {
                                    $element =& HTML_QuickForm::createElement($this->_getQFType('integer'), $key, $this->getFieldLabel($key));
                                }
                                $elValidator = 'numeric';
                                break;
                            case ($type & DB_DATAOBJECT_DATE): // TODO
                                $element =& $this->_createDateElement($key);
                                break;
                            case ($type & DB_DATAOBJECT_DATE & DB_DATAOBJECT_TIME):
                                $element =& $this->_createDateElement($key);  
                                break;  
                            case ($type & DB_DATAOBJECT_TIME): // TODO  
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
                                } else if (!isset($element)) {
                                    $element =& HTML_QuickForm::createElement($this->_getQFType('shorttext'), $key, $this->getFieldLabel($key));
                                }
                                break;
                            case ($type & DB_DATAOBJECT_FORMBUILDER_CROSSLINK):
                                unset($element);
                                $form->addGroup(array(), $key, $key, '<br/>');
                                break;
                            case ($type & DB_DATAOBJECT_FORMBUILDER_TRIPLELINK):
                                $element =& HTML_QuickForm::createElement('static', $key, $key);
                                break;
                            default:
                                if (!isset($element)) {
                                    $element =& HTML_QuickForm::createElement('text', $key, $this->getFieldLabel($key));
                                }
                        } // End switch
                    } // End else                

                    if ($elValidator !== false) {
                        $rules[$key][] = array('validator' => $elValidator, 'rule' => $elValidRule);
                    } // End if
                                        
                } // End else
            } // End else
                    
            //GROUP OR ELEMENT ADDITION
            if(isset($groupelements) && in_array($key, $groupelements)) {
                $group = $this->_do->preDefGroups[$key];
                $groups[$group][] = $element;
            } elseif (isset($element)) {
                $form->addElement($element);
            } // End if
            
            //ADD REQURED RULE FOR NOT_NULL FIELDS
            if ((!in_array($key, $keys) || $hidePrimary === false) && ($type & DB_DATAOBJECT_NOTNULL)) {
                $form->addRule($key, sprintf($this->required_rule_message, $key), 'required');
            }

            //VALIDATION RULES
            if (isset($rules[$key])) {
                reset($rules[$key]);
                while(list($n, $rule) = each($rules[$key])) {
                    if ($rule['rule'] === false) {
                        $form->addRule($key, sprintf($this->rule_violation_message, $key), $rule['validator']);
                    } else {
                        $form->addRule($key, sprintf($this->rule_violation_message, $key), $rule['validator'], $rule['rule']);
                    } // End if
                } // End while
            } // End if     
        } // End foreach

        // Freeze fields that are not to be edited by the user
        $user_editable_fields = $this->_getUserEditableFields();
        if (is_array($user_editable_fields)) {
            $elements_to_freeze = array_diff(array_keys($elements), $user_editable_fields);
        } else {
            $elements_to_freeze = null;
        }
        foreach($elements_to_freeze as $element_to_freeze) {
            if($form->elementExists($element_to_freeze)) {
                $el =& $form->getElement($element_to_freeze);
                $el->freeze();
            }
        }
        
        // CREATE SUBMIT BUTTON?
        $createSubmit = true;
        if (isset($this->_do->createSubmit) && $this->_do->createSubmit == false) {
            $createSubmit = false;
        } elseif (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['createSubmit']) &&
                        $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['createSubmit'] == 0) {
            $createSubmit = false;
        }
        
        
        //GROUP SUBMIT
        $flag = true;
        if(isset($groupelements) && in_array('__submit__', $groupelements)) {
            $group = $this->_do->preDefGroups['__submit__'];
            if(count($groups[$group]) > 1) {
                $groups[$group][] =& HTML_QuickForm::createElement('submit', '__submit__', 'Submit');
                $flag = false;
            } else {
                $flag = true;
            }   
        }
        
        // generate triplelink stuff
        // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
        if (count($this->_do->_tripleLinks) > 0) {
            // primary key detection taken from getSelectOptions() so it doesn't allow
            // the use of multiple keys... this should be improved in the future if possible imho..
            if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }
            if (empty($pk)) {
                return PEAR::raiseError('A primary key must exist in the base table when using _tripleLinks.');
            }
            foreach ($this->_do->_tripleLinks as $triplelink) {
                $elName  = '__triplelink_' . $triplelink['table'];
                if($form->elementExists($elName)) {
                    $freeze = array_search('__triplelink_' . $triplelink['table'], $elements_to_freeze);
                    $do = DB_DataObject::factory($triplelink['table']);
                    if (PEAR::isError($do)) {
                        die($do->getMessage());
                    }

                    $links = $do->links();

                    if(isset($triplelink['from_field'])) {
                        $from_field = $triplelink['from_field'];
                    } else {
                        unset($from_field);
                    }
                    if(isset($triplelink['to_field_1'])) {
                        $to_field_1 = $triplelink['to_field_1'];
                    } else {
                        unset($to_field_1);
                    }
                    if(isset($triplelink['to_field_2'])) {
                        $to_field_2 = $triplelink['to_field_2'];
                    } else {
                        unset($to_field_2);
                    }
                    if(!isset($to_field_2) || !isset($to_field_1) || !isset($from_field)) {
                        foreach($links as $field => $link) {
                            list($linkTable, $linkField) = explode(':', $link);
                            if(!isset($from_field) && $linkTable == $this->_do->__table) {
                                $from_field = $field;
                            } else if(!isset($to_field_1) && $linkField != $from_field) {
                                $to_field_1 = $field;
                            } else if(!isset($to_field_2) && $linkField != $from_field && $linkField != $to_field_1) {
                                $to_field_2 = $field;
                            }
                        }
                    }

                    list($linkedtable1, $linkedfield1) = explode(':', $links[$to_field_1]);
                    list($linkedtable2, $linkedfield2) = explode(':', $links[$to_field_2]);

                    $all_options1 = $this->_getSelectOptions($linkedtable1);
                    $all_options2 = $this->_getSelectOptions($linkedtable2);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $do->$from_field = $this->_do->$pk;
                        if ($do->find() > 0) {
                            while ($do->fetch()) {
                                $selected_options[$do->$to_field_1][] = $do->$to_field_2;
                            }
                        }
                    }

                    include_once 'HTML/Table.php';
                    $table = new HTML_Table();
                    $table->setAutoGrow(true);
                    $table->setAutoFill('');
                    $row = 0;
                    $col = 0;
                    foreach($all_options2 as $key2=>$value2) {
                        $col++;
                        $table->setCellContents($row, $col, $value2);
                        $table->setCellAttributes($row, $col, array('style' => 'text-align: center'));
                    }
                    foreach($all_options1 as $key1=>$value1) {
                        $row++;
                        $col = 0;
                        $table->setCellContents($row, $col, $value1);
                        foreach($all_options2 as $key2=>$value2) {
                            $col++;
                            $element = HTML_QuickForm::createElement('checkbox', '__triplelink_' . $triplelink['table'] . '[' . $key1 . '][]', null, null);
                            $element->updateAttributes(array('value' => $key2));
                            if($freeze) {
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
                    $elLabel = (!empty($this->_do->fieldLabels[$elName])) ? $this->_do->fieldLabels[$elName] : $elName;
                    $linkElement =& $form->getElement($elName);
                    $linkElement->setLabel($elLabel);
                    $linkElement->setValue($table->toHTML());
                }
            }
        }

        // generate crosslink stuff
        // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
        if (count($this->_do->_crossLinks) > 0) {
            // primary key detection taken from getSelectOptions() so it doesn't allow
            // the use of multiple keys... this should be improved in the future if possible imho..
            if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }
            if (empty($pk)) {
                return PEAR::raiseError('A primary key must exist in the base table when using _crossLinks.');
            }
            foreach ($this->_do->_crossLinks as $crosslinkindex => $crosslink) {
                $groupName  = '__crosslink_' . $crosslink['table'];
                if($form->elementExists($groupName)) {
                    $linkGroup =& $form->getElement($groupName);
                    $do = DB_DataObject::factory($crosslink['table']);
                    if (PEAR::isError($do)) {
                        die($do->getMessage());
                    }
                    $links = $do->links();

                    if(isset($crosslink['from_field'])) {
                        $from_field = $crosslink['from_field'];
                    } else {
                        unset($from_field);
                    }
                    if(isset($crosslink['to_field'])) {
                        $to_field = $crosslink['to_field'];
                    } else {
                        unset($to_field);
                    }
                    if(!isset($to_field) || !isset($from_field)) {
                        foreach($links as $field => $link) {
                            list($linkTable, $linkField) = explode(':', $link);
                            if(!isset($from_field) && $linkTable == $this->_do->__table) {
                                $from_field = $field;
                            } else if(!isset($to_field) && $linkField != $from_field) {
                                $to_field = $field;
                            }
                        }
                    }

                    list($linkedtable, $linkedfield) = explode(':', $links[$to_field]);
                    $all_options      = $this->_getSelectOptions($linkedtable);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $do->$from_field = $this->_do->$pk;
                        if ($do->find() > 0) {
                            while ($do->fetch()) {
                                $selected_options[] = $do->$to_field;
                            }
                        }
                    }
                    //print_r($do);

                    // ***X*** generate checkboxes
                
                    $grp = array();
                    foreach($all_options as $key=>$value) {
                        $element = HTML_QuickForm::createElement('checkbox', '', null, $value);
                        $element->updateAttributes(array('value' => $key));
                        if (in_array($key, $selected_options)) {
                            $element->setChecked(true);
                        }
                        $grp[] = $element;
                    }
                    $groupLabel = (!empty($this->_do->fieldLabels[$groupName])) ? $this->_do->fieldLabels[$groupName] : $groupName;
                    $linkGroup->setLabel($groupLabel);
                    $linkGroup->setElements($grp);
                
                    // ***X*** OR

                    // ***X*** generate a <select>
                    /*
                $fullcrosslinktablename = '__crosslink_' . $crosslink['table'];
                if (empty($this->_do->fieldLabels[$fullcrosslinktablename])) {
                    $caption = $fullcrosslinktablename;
                } else {
                    $caption = $this->_do->fieldLabels[$fullcrosslinktablename];                    
                }
                $element =& HTML_QuickForm::createElement('select', $fullcrosslinktablename, $caption, $all_options, array('multiple' => 'multiple'));
                $form->addElement($element);
                $formValues['__crosslink_' . $crosslink['table']] = $selected_options; // set defaults later
                    */
                }
            }
        }

        //GROUPING  
        if(isset($groups) && is_array($groups)) { //apply grouping
            reset($groups);
            while(list($grp, $elements) = each($groups)) {
                if(count($elements) == 1) {  
                    $form->addElement($elements);
                } elseif(count($elements) > 1) {
                    $form->addGroup($elements, $grp, $grp, '&nbsp;');
                }
            }       
        }

        //ELEMENT SUBMIT
        if($flag == true && $createSubmit == true) {
            $submitText = 'Submit';
            if (isset($this->_do->submitText)) {
                $submitText = $this->_do->submitText;
            } elseif (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['submitText'])) {
                $submitText = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['submitText'];
            }
            $form->addElement('submit', '__submit__', $submitText);
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
        global $_DB_DATAOBJECT_FORMBUILDER;
        $dateOptions = array('format' => $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['date_element_format']);
        if (method_exists($this->_do, 'dateoptions')) {
            $dateOptions = array_merge($dateOptions, $this->_do->dateOptions($name));
        }
        $element =& HTML_QuickForm::createElement($this->_getQFType('date'), $name, $this->getFieldLabel($name), $dateOptions);
        
        // Convert date from database into a format usable with the date element (default: array)
        if ($this->_dateFromDatabaseCallback != false && function_exists($this->_dateFromDatabaseCallback)) {
            $this->debug("DATE CONVERSION using callback for element $name ({$this->_do->$name})!", "FormBuilder");
            $formValues[$name] = call_user_func($this->_dateFromDatabaseCallback, $this->_do->$name);
        }  
        return $element;  
    }

    /**
     * DB_DataObject_FormBuilder::_reorderElements()
     *
     * Changes the order in which elements are being processed, so that
     * you can use QuickForm's default renderer or dynamic templates without
     * being dependent on the field order in the database.
     *
     * Make a class property named "preDefOrder" in your DataObject-derived classes
     * which contains an array with the correct element order to use this feature.
     *
     * @return mixed  Array in correct order or FALSE if reordering was not possible
     * @access protected
     * @author Fabien Franzen <atelierfabien@home.nl>
     */
    function _reorderElements() {
        if(isset($this->_do->preDefOrder) && is_array($this->_do->preDefOrder)) {
            $this->debug("<br/>...reordering elements...<br/>");
            $elements = $this->_getFieldsToRender();
            $table = $this->_do->table();
            $crossLinks = $this->_getCrossLinkElementNames();

            foreach($this->_do->preDefOrder as $elem) {
                if(isset($elements[$elem])) {
                    $ordered[$elem] = $elements[$elem]; //key=>type
                } else if(!isset($table[$elem]) && !isset($crossLinks[$elem])) {
                    $this->debug('<br/>...reorder not supported: invalid element(key) found...<br/>');
                    return false;
                }
            }
            return $ordered;
        } else {
            $this->debug('<br/>...reorder not supported...<br/>');
            return false;
        }
    }

    function _getCrossLinkElementNames() {
        $ret = array();
        if(isset($this->_do->_tripleLinks)) {
            foreach($this->_do->_tripleLinks as $tripleLink) {
                $ret['__triplelink_'.$tripleLink['table']] = DB_DATAOBJECT_FORMBUILDER_TRIPLELINK;
            }
        }
        if(isset($this->_do->_crossLinks)) {
            foreach($this->_do->_crossLinks as $crossLink) {
                $ret['__crosslink_'.$crossLink['table']] = DB_DATAOBJECT_FORMBUILDER_CROSSLINK;
            }
        }
        return $ret;
    }
    
    
    /**
     * DB_DataObject_FormBuilder::useForm()
     *
     * Sometimes, it might come in handy not just to create a new QuickForm object,
     * but to work with an existing one. Using FormBuilder together with
     * HTML_QuickForm_Controller or HTML_QuickForm_Page is such an example ;-)
     * If you do not call this method before the form is generated, a new QuickForm
     * object will be created (default behaviour).
     *
     * @param $form     object  A HTML_QuickForm object (or extended from that)
     * @param $append   boolean If TRUE, the form will be appended to the one generated by FormBuilder. If false, FormBuilder will just add its own elements to this form. 
     * @return boolean  Returns false if the passed object was not a HTML_QuickForm object or a QuickForm object was already created
     * @access public
     */
    function useForm(&$form, $append=false)
    {
        if (is_a($form, 'html_quickform') && !is_object($this->_form)) {
            $this->_form =& $form;
            $this->_appendForm = $append;
            return true;
        }
        return false;
    }
    



    /**
     * DB_DataObject_FormBuilder::getFieldLabel()
     *
     * Returns the label for the given field name. If no label is specified,
     * the fieldname will be returned with ucfirst() applied.
     *
     * @param $fieldName  string  The field name
     * @return string
     * @access public
     */
    function getFieldLabel($fieldName)
    {
        if (isset($this->_do->fieldLabels[$fieldName])) {
            return $this->_do->fieldLabels[$fieldName];
        }
        return ucfirst($fieldName);
    }

    /**
     * DB_DataObject_FormBuilder::getDataObjectSelectDisplayValue()
     *
     * Returns a string which identitfies this dataobject.
     * If multiple display fields are given, will display them all seperated by ", ".
     * If a display field is a foreign key (link) the display value for the record it
     * points to will be used. (Its display value will be surrounded by parenthesis
     * as it may have multiple display fields of its own.)
     *
     * Will use display field configurations from these locations, in this order:<br/>
     * 1) $displayfield parameter<br/>
     * 2) databaseName.formBuilder.ini file, section [tableName__display_fields]<br/>
     * 3) the select_display_field member variable of the dataobject<br/>
     * 4) global 'select_display_field' setting for DB_DataObject_FormBuilder
     *
     *
     * @param DB_DataObject the dataobject to get the display value for, must be populated
     * @param mixed field to use to display, may be an array with field names or a single field
     * @return string select display value for this field
     * @access public
     */
    function getDataObjectSelectDisplayValue(&$do, $displayfield = false, $level = 1) {
        global $_DB_DATAOBJECT_FORMBUILDER;
        $links = $do->links();
        /*if ($displayfield === false) {
            if (isset($_DB_DATAOBJECT_FORMBUILDER['INI'][$do->database()][$do->tableName().'__display_fields'])) {
                $displayfield = $_DB_DATAOBJECT_FORMBUILDER['INI'][$do->database()][$do->tableName().'__display_fields'];
            } else if (isset($do->select_display_field) && !is_null($do->select_display_field)) {
                $displayfield = $do->select_display_field;
            } else {
                $displayfield = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'];
            }
        }*/
        if ($displayfield == false) {
            if(isset($_DB_DATAOBJECT_FORMBUILDER['INI'][$do->database()][$do->tableName().'__display_fields'])) {
                $displayfield = $_DB_DATAOBJECT_FORMBUILDER['INI'][$do->database()][$do->tableName().'__display_fields'];
            } else if(isset($do->select_display_field) && !is_null($do->select_display_field)) {
                $displayfield = $do->select_display_field;
            } else if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field']) &&
                       !empty($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'])) {
                $displayfield = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'];
            } else {
                $displayfield = $pk;
            }
        }
        if (!is_array($displayfield)) {
            $displayfield = array($displayfield);
        }
        $ret = '';
        $first = true;
        foreach ($displayfield as $field) {
            if ($first) {
                $first = false;
            } else {
                $ret .= ', ';
            }
            if (isset($do->$field)) {
                if(@$_DB_DATAOBJECT_FORMBUILDER['CONFIG']['follow_links'] > $level && isset($links[$field])
                   && ($subDo = $do->getLink($field))) {
                    $ret .= '('.$this->getDataObjectSelectDisplayValue($subDo, false, $level + 1).')';
                } else {
                    $ret .= $do->$field;
                }
            }
        }
        return $ret;
    }

    /**
     * DB_DataObject_FormBuilder::getSelectOptions()
     *
     * Returns an array of options for use with the HTML_QuickForm "select" element.
     * It will try to fetch all related objects (if any) for the given field name and
     * build the array.
     * For the display name of the option, it will try to use
     * the settings in the database.formBuilder.ini file. If those are not found,
     * the linked object's property "select_display_field". If that one is not present,
     * it will try to use the global configuration setting "select_display_field".
     * Can also be called with a second parameter containing the name of the display
     * field - this will override all other settings.
     * Same goes for "select_order_field", which determines the field name used for
     * sorting the option elements. If neither a config setting nor a class property
     * of that name is set, the display field name will be used.
     *
     * @param string $field         The field to fetch the links from. You should make sure the field actually *has* links before calling this function (see: DB_DataObject::links())
     * @param string $displayField  (Optional) The name of the field used for the display text of the options
     * @return array
     * @access public
     */
    function getSelectOptions($field, $displayfield=false)
    {
        global $_DB_DATAOBJECT_FORMBUILDER;
        if (empty($this->_do->_database)) {
            // TEMPORARY WORKAROUND !!! Guarantees that DataObject config has
            // been loaded and all link information is available.
            $this->_do->keys();   
        }
        $links = $this->_do->links();
        $link = explode(':', $links[$field]);

        $res = $this->_getSelectOptions($link[0], $displayfield);

        if ($res !== false) {
            return $res;
        }

        $this->debug('Error: '.get_class($opts).' does not inherit from DB_DataObject');
        return array();
    }

    function _getSelectOptions($table, $displayfield = false) {
        global $_DB_DATAOBJECT_FORMBUILDER;

        $opts = DB_DataObject::factory($table);
        if (is_a($opts, 'db_dataobject')) {
            if (isset($opts->_primary_key)) {
                $pk = $opts->_primary_key;
            } else {
                $k = $opts->keys();
                $pk = $k[0];
            }
            /*
            if ($displayfield == false) {
                if(isset($_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__display_fields'])) {
                    $displayfield = $_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__display_fields'];
                } else if (!isset($opts->select_display_field) || is_null($opts->select_display_field)) {
                    $displayfield = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'];
                } else {
                    $displayfield = $opts->select_display_field;
                }
            }
            */
            if ($displayfield == false) {
                if(isset($_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__display_fields'])) {
                    $displayfield = $_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__display_fields'];
                } else if(isset($opts->select_display_field) && !is_null($opts->select_display_field)) {
                    $displayfield = $opts->select_display_field;
                } else if (isset($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field']) &&
                           !empty($_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'])) {
                    $displayfield = $_DB_DATAOBJECT_FORMBUILDER['CONFIG']['select_display_field'];
                } else {
                    $displayfield = $pk;
                }
            }
            if (!isset($opts->select_order_field) || is_null($opts->select_order_field)) {
                if(isset($_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__order_fields'])) {
                    $order = $_DB_DATAOBJECT_FORMBUILDER['INI'][$opts->database()][$opts->tableName().'__order_fields'];
                } else {
                    $order = $displayfield;
                }
            } else {
                $order = $opts->select_order_field;
            }
            if(is_array($order)) {
                $orderStr = '';
                $first = true;
                foreach($order as $col) {
                    if($first) {
                        $first = false;
                    } else {
                        $orderStr .= ', ';
                    }
                    $orderStr .= $col;
                }
            } else {
                $orderStr = $order;
            }
            $opts->orderBy($orderStr);
            $list = array();

            // FIXME!
            if (isset($opts->select_add_empty) && $opts->select_add_empty == true) {
                $list[''] = '';
            }
            
            // FINALLY, let's see if there are any results
            if ($opts->find() > 0) {
                while ($opts->fetch()) {
                    $list[$opts->$pk] = $this->getDataObjectSelectDisplayValue($opts, $displayfield);
                }
            }

            return $list;
        }
        $this->debug('Error: '.get_class($opts).' does not inherit from DB_DataObject');
        return array();
    }


    /**
     * DB_DataObject_FormBuilder::getForm()
     *
     * Returns a HTML form that was automagically created by _generateForm().
     * You need to use the get() method before calling this one in order to
     * prefill the form with the retrieved data.
     *
     * If you have a method named "preGenerateForm()" in your DataObject-derived class,
     * it will be called before _generateForm(). This way, you can create your own elements
     * there and add them to the "preDefElements" property, so they will not be auto-generated.
     *
     * If you have your own "getForm()" method in your class, it will be called <b>instead</b> of
     * _generateForm(). This enables you to have some classes that make their own forms completely
     * from scratch, without any auto-generation. Use this for highly complex forms. Your getForm()
     * method needs to return the complete HTML_QuickForm object by reference.
     *
     * If you have a method named "postGenerateForm()" in your DataObject-derived class, it will
     * be called after _generateForm(). This allows you to remove some elements that have been
     * auto-generated from table fields but that you don't want in the form.
     *
     * Many ways lead to rome.
     *
     * @param string $action   The form action. Optional. If set to false (default), $_SERVER['PHP_SELF'] is used.
     * @param string $target   The window target of the form. Optional. Defaults to '_self'.
     * @param string $formName The name of the form, will be used in "id" and "name" attributes. If set to false (default), the class name is used, prefixed with "frm"
     * @param string $method   The submit method. Defaults to 'post'.
     * @return object
     * @access public
     */
    function &getForm($action=false, $target='_self', $formName=false, $method='post')
    {
        if (method_exists($this->_do, 'pregenerateform')) {
            $this->_do->preGenerateForm($this);
        }
        if (method_exists($this->_do, 'getform')) {
            $obj = $this->_do->getForm($action, $target, $formName, $method);
        } else {
            $obj = &$this->_generateForm($action, $target, $formName, $method);
        }
        if (method_exists($this->_do, 'postgenerateform')) {
            
            $this->_do->postGenerateForm(&$obj);
        }
        return($obj);   
    }


    /**
     * DB_DataObject_FormBuilder::_date2array()
     *
     * Takes a string representing a date or a unix timestamp and turns it into an
     * array suitable for use with the QuickForm data element.
     * When using a string, make sure the format can be handled by the PEAR::Date constructor!
     *
     * Beware: For the date conversion to work, you must at least use the letters "d", "m" and "Y" in
     * your format string (see "date_element_format" option). If you want to enter a time as well,
     * you will have to use "H", "i" and "s" as well. Other letters will not work! Exception: You can
     * also use "M" instead of "m" if you want plain text month names.
     *
     * @param mixed $date   A unix timestamp or the string representation of a date, compatible to strtotime()
     * @return array
     * @access protected
     */
    function _date2array($date)
    {
        $da = array();
        if (is_string($date)) {
            // Get PEAR::Date class definition, if needed
            include_once('Date.php');
            $dObj = new Date($date);
            $da['d'] = $dObj->getDay();
            $da['m'] = $dObj->getMonth();
            $da['M'] = $dObj->getMonth();
            $da['Y'] = $dObj->getYear();
            $da['H'] = $dObj->getHour();
            $da['i'] = $dObj->getMinute();
            $da['s'] = $dObj->getSecond();
            unset($dObj);
        } else {
            if (is_int($date)) {
                $time = $date;
            } else {
                $time = time();
            }
            $da['d'] = date('d', $time);
            $da['m'] = date('m', $time);
            $da['M'] = date('m', $time);
            $da['Y'] = date('Y', $time);
            $da['H'] = date('H', $time);
            $da['i'] = date('i', $time);
            $da['s'] = date('s', $time);
        }
        $this->debug("<i>_date2array():</i> from $date ...");
        return $da;
    }


    /**
     * DB_DataObject_FormBuilder::_array2date()
     *
     * Takes a date array as used by the QuickForm date element and turns it back into
     * a string representation suitable for use with a database date field (format 'YYYY-MM-DD').
     * If second parameter is true, it will return a unix timestamp instead.
     *
     * Beware: For the date conversion to work, you must at least use the letters "d", "m" and "Y" in
     * your format string (see "date_element_format" option). If you want to enter a time as well,
     * you will have to use "H", "i" and "s" as well. Other letters will not work! Exception: You can
     * also use "M" instead of "m" if you want plain text month names.
     *
     * @param array $date   An array representation of a date, as user in HTML_QuickForm's date element
     * @param boolean $timestamp  Optional. If true, return a timestamp instead of a string. Defaults to false.
     * @return mixed
     * @access protected
     */
    function _array2date($dateInput, $timestamp=false)
    {
        if (isset($dateInput['M'])) {
            $month = $dateInput['M'];
        } else {
            $month = $dateInput['m'];   
        }
        $strDate = sprintf('%s-%s-%s', $dateInput['Y'], $month, $dateInput['d']);
        if (isset($dateInput['H']) && isset($dateInput['i']) && isset($dateInput['s'])) {
            $strDate .= sprintf(' %s:$s:$s', $dateInput['H'], $dateInput['i'], $dateInput['s']);
        }
        $this->debug("<i>_array2date():</i> to $strDate ...");
        return $strDate;
    }

    /**
     * DB_DataObject_FormBuilder::validateData()
     *
     * Makes a call to the current DataObject's validate() method and returns the result.
     *
     * @return mixed
     * @access public
     * @see DB_DataObject::validate()
     */
    function validateData()
    {
        $this->_validationErrors = $this->_do->validate();
        return $this->_validationErrors;
    }

    /**
     * DB_DataObject_FormBuilder::getValidationErrors()
     *
     * Returns errors from data validation. If errors have occured, this will be
     * an array with the fields that have errors, otherwise a boolean.
     *
     * @return mixed
     * @access public
     * @see DB_DataObject::validate()
     */
    function getValidationErrors()
    {
        return $this->_validationErrors;
    }


    /**
     * DB_DataObject_FormBuilder::processForm()
     *
     * This will take the submitted form data and put it back into the object's properties.
     * If the primary key is not set or NULL, it will be assumed that you wish to insert a new
     * element into the database, so DataObject's insert() method is invoked.
     * Otherwise, an update() will be performed.
     * <i><b>Careful:</b> If you're using natural keys or cross-referencing tables where you don't have
     * one dedicated primary key, this will always assume that you want to do an update! As there
     * won't be a matching entry in the table, no action will be performed at all - the reason
     * for this behaviour can be very hard to detect. Thus, if you have such a situation in one
     * of your tables, simply override this method so that instead of the key check it will try
     * to do a SELECT on the table using the current settings. If a match is found, do an update.
     * If not, do an insert.</i>
     * This method is perfect for use with QuickForm's process method. Example:
     * <code>
     * if ($form->validate()) {
     *     $form->freeze();
     *     $form->process(array(&$formGenerator,'processForm'), false);
     * }
     * </code>
     *
     * If you wish to enforce a special type of query, use the forceQueryType() method.
     *
     * Always remember to pass your objects by reference - otherwise, if the operation was
     * an insert, the primary key won't get updated with the new database ID because processForm()
     * was using a local copy of the object!
     *
     * If a method named "preProcess()" exists in your derived class, it will be called before
     * processForm() starts doing its magic. The data that has been submitted by the form
     * will be passed to that method as a parameter.
     * Same goes for a method named "postProcess()", with the only difference - you might
     * have guessed this by now - that it's called after the insert/update operations have
     * been done. Use this for filtering data, notifying users of changes etc.pp. ...
     *
     * @param array $values   The values of the submitted form
     * @param string $queryType If the standard query behaviour ain't good enough for you, you can force a certain type of query
     * @return boolean        TRUE if database operations were performed, FALSE if not
     * @access public
     */
    function processForm($values)
    {
        $this->debug("<br>...processing form data...<br>");
        if (method_exists($this->_do, 'preprocess')) {
            $this->_do->preProcess($values);
        }
        
        $editableFields = $this->_getUserEditableFields();

        foreach ($values as $field=>$value) {
            $this->debug("Field $field ");
            // Double-check if the field may be edited by the user... if not, don't
            // set the submitted value, it could have been faked!
            if (in_array($field, $editableFields)) {
                if (in_array($field, array_keys($this->_do->table()))) {
                    if (is_array($value)) {
                        if (isset($value['tmp_name'])) {
                            $this->debug(" (converting file array) ");
                            $value = $value['name'];
                        } else {
                            $this->debug("DATE CONVERSION using callback from $value ...");
                            $value = call_user_func($this->_dateToDatabaseCallback, $value);
                        }
                    } elseif (isset($this->_do->dateFields) && in_array($field, $this->_do->dateFields)) {
                        $this->debug("DATE CONVERSION using callback from $value ...");
                        $value = call_user_func($this->_dateToDatabaseCallback, $value);
                    }
                    $this->debug("is substituted with '$value'.\n");
                    // See if a setter method exists in the DataObject - if so, use that one
                    if (method_exists($this->_do, 'set' . $field)) {
                        $this->_do->{'set'.$field}($value);
                    } else {
                        // Otherwise, just set the property 'normally'...
                        $this->_do->$field = $value;
                    }
                } else {
                    $this->debug("is not a valid field.\n");
                }
            } else {
                $this->debug('is defined not to be editable by the user!');   
            }
        }

        $dbOperations = true;
        if ($this->validateOnProcess === true) {
            $this->debug('Validating data... ');
            if (is_array($this->validateData())) {
                $dbOperations = false;
            }
        }

        // Data is valid, let's store it!
        if ($dbOperations) {
            $action = $this->_queryType;
            if ($this->_queryType == DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT) {
                if (isset($this->_do->primary_key)) {
                    $pk = $this->_do->primary_key;
                } else {
                    $keys = $this->_do->keys();
                    if (is_array($keys) && isset($keys[0])) {
                        $pk = $keys[0];
                    }
                }
            
                // Could the primary key be detected?
                if (!isset($pk)) {
                    // Nope, so let's exit and return false. Sorry, you can't store data using
                    // processForm with this DataObject unless you do some tweaking :-(
                    $this->debug('Primary key not detected - storing data not possible.');
                    return false;   
                }
                
                $action = DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE;
                if (empty($this->_do->$pk) || is_null($this->_do->$pk)) {
                    $action = DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT;
                }
            }
            
            switch ($action) {
                case DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT:
                    $id = $this->_do->insert();
                    $this->debug("ID ($pk) of the new object: $id\n");
                    break;
                case DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE:
                    $this->_do->update();
                    $this->debug("Object updated.\n");
                    break;
            }

            // process triplelink stuff
            if (!empty($this->_do->id)) { // has only sense if we have a valid id
                if (count($this->_do->_tripleLinks) > 0) {
                    foreach ($this->_do->_tripleLinks as $triplelink) {
                        $do = DB_DataObject::factory($triplelink['table']);

                        $links = $do->links();

                        if(isset($triplelink['from_field'])) {
                            $from_field = $triplelink['from_field'];
                        } else {
                            unset($from_field);
                        }
                        if(isset($triplelink['to_field_1'])) {
                            $to_field_1 = $triplelink['to_field_1'];
                        } else {
                            unset($to_field_1);
                        }
                        if(isset($triplelink['to_field_2'])) {
                            $to_field_2 = $triplelink['to_field_2'];
                        } else {
                            unset($to_field_2);
                        }
                        if(!isset($to_field_2) || !isset($to_field_1) || !isset($from_field)) {
                            foreach($links as $field => $link) {
                                list($linkTable, $linkField) = explode(':', $link);
                                if(!isset($from_field) && $linkTable == $this->_do->__table) {
                                    $from_field = $field;
                                } else if(!isset($to_field_1) && $linkField != $from_field) {
                                    $to_field_1 = $field;
                                } else if(!isset($to_field_2) && $linkField != $from_field && $linkField != $to_field_1) {
                                    $to_field_2 = $field;
                                }
                            }
                        }


                        $do->$from_field = $this->_do->id;
                        $do->delete();
            
                        $rows = $values['__triplelink_' . $triplelink['table']];
                        if (count($rows) > 0) {
                            foreach ($rows as $rowid=>$row) {
                                if (count($row) > 0) {
                                    foreach ($row as $fieldvalue) {
                                        $do = DB_DataObject::factory($triplelink['table']);
                                        $do->$from_field = $this->_do->id;
                                        $do->$to_field_1 = $rowid;
                                        $do->$to_field_2 = $fieldvalue;
                                        $do->insert();
                                    }
                                }
                                
                            }
                        }
                    }
                }
            
                if (count($this->_do->_crossLinks) > 0) {
                    foreach ($this->_do->_crossLinks as $crosslink) {
                        $do = DB_DataObject::factory($crosslink['table']);
                        $links = $do->links();

                        //after $links = $do->links(); in the crossLinks code
                        if(isset($crosslink['from_field'])) {
                            $from_field = $crosslink['from_field'];
                        } else {
                            unset($from_field);
                        }
                        if(isset($crosslink['to_field'])) {
                            $to_field = $crosslink['to_field'];
                        } else {
                            unset($to_field);
                        }
                        if(!isset($to_field) || !isset($from_field)) {
                            foreach($links as $field => $link) {
                                list($linkTable, $linkField) = explode(':', $link);
                                if(!isset($from_field) && $linkTable == $this->_do->__table) {
                                    $from_field = $field;
                                } else if(!isset($to_field) && $linkField != $from_field) {
                                    $to_field = $field;
                                }
                            }
                        }

                        $do->$from_field = $this->_do->id;
                        $do->delete();
                        $fieldvalues = $values['__crosslink_' . $crosslink['table']];
                        if (count($fieldvalues) > 0) {
                            foreach ($fieldvalues as $fieldvalue) {
                                $do = DB_DataObject::factory($crosslink['table']);
                                $do->$from_field = $this->_do->id;
                                $do->$to_field = $fieldvalue;
                                $do->insert();
                            }
                        }
                    }
                }
            }
        }

        if (method_exists($this->_do, 'postprocess')) {
            $this->_do->postProcess($values);
        }

        return $dbOperations;
    }
    
    
    /**
     * DB_DataObject_FormBuilder::forceQueryType()
     *
     * You can force the behaviour of the processForm() method by passing one of
     * the following constants to this method:
     *
     * - DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT:
     *   The submitted data will always be INSERTed into the database
     * - DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE:
     *   The submitted data will always be used to perform an UPDATE on the database
     * - DB_DATAOBJECT_FORMBUILDER_QUERY_FORCENOACTION:
     *   The submitted data will overwrite the properties of the DataObject, but no
     *   action will be performed on the database.
     * - DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT:
     *   The processForm() method will try to detect for itself if an INSERT or UPDATE
     *   query has to be performed. This will not work if no primary key field can
     *   be detected for the current DataObject. In this case, no action will be performed.
     *   This is the default behaviour.
     *
     * @param integer $queryType The type of the query to be performed. Please use the preset constants for setting this.
     * @return boolean
     * @access public
     */
    function forceQueryType($queryType=DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT)
    {
        switch ($queryType) {
            case DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT:
            case DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE:
            case DB_DATAOBJECT_FORMBUILDER_QUERY_FORCENOACTION:
            case DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT:
                $this->_queryType = $queryType;
                return true;
                break;
            default:
                return false;
        }
    }


    /**
     * DB_DataObject_FormBuilder::debug()
     *
     * Outputs a debug message, if the debug setting in the DataObject.ini file is
     * set to 1 or higher.
     *
     * @param string $message  The message to printed to the browser
     * @access public
     * @see DB_DataObject::debugLevel()
     */
    function debug($message)
    {
        if (DB_DataObject::debugLevel() > 0) {
            echo "<pre><b>FormBuilder:</b> $message</pre>\n";
        }
    }
    
    /**
     * DB_DataObject_FormBuilder::_getFieldsToRender()
     *
     * If the "fieldsToRender" property in a DataObject is not set, all fields
     * will be rendered as form fields.
     * When the property is set, a field will be rendered only if:
     * 1. it is a primary key
     * 2. it's explicitly requested in $do->fieldsToRender
     *
     * @access private
     * @return array   The fields that shall be rendered
     */
    function _getFieldsToRender()
    {
        $all_fields = array_merge($this->_do->table(), $this->_getCrossLinkElementNames());
        if (isset($this->_do->fieldsToRender) && is_array($this->_do->fieldsToRender)) {
            // a little workaround to get an array like [FIELD_NAME] => FIELD_TYPE (for use in _generateForm)
            // maybe there's some better way to do this:
            $result = array();

            $key_fields = $this->_do->keys();
            if (!is_array($key_fields)) {
                $key_fields = array();
            }
            $fields_to_render = $this->_do->fieldsToRender;

            if (is_array($all_fields)) {
                foreach ($all_fields as $key=>$value) {
                    if ( (in_array($key, $key_fields)) || (in_array($key, $fields_to_render)) ) {
                        $result[$key] = $all_fields[$key];
                    }
                }
            }

            if (count($result) > 0) {
                return $result;
            }
            return $all_fields;
        }
        return $all_fields;
    }
    
    
    /**
     * DB_DataObject_FormBuilder::_getUserEditableFields()
     *
     * Normally, all fields in a form are editable by the user. If you want to
     * make some fields uneditable, you have to set the "userEditableFields" property
     * with an array that contains the field names that actually can be edited.
     * All other fields will be freezed (which means, they will still be a part of
     * the form, and they values will still be displayed, but only as plain text, not
     * as form elements).
     *
     * @access private
     * @return array   The fields that shall be editable.
     */
    function _getUserEditableFields()
    {
        // if you don't want any of your fields to be editable by the user, set userEditableFields to
        // "array()" in your DataObject-derived class
        if (isset($this->_do->userEditableFields) && is_array($this->_do->userEditableFields)) {
            return $this->_do->userEditableFields;
        }
        // all fields may be updated by the user since userEditableFields is not set
        if (isset($this->_do->fieldsToRender) && is_array($this->_do->fieldsToRender)) {
            return $this->_do->fieldsToRender;
        }
        return array_keys($this->_do->table());
    }
    
    /**
     * DB_DataObject_FormBuilder::_getQFType()
     *
     * Returns the QuickForm element type associated with the given field type,
     * as defined in the _elementTypeMap property. If an unknown field type is given,
     * the returned type name will default to 'text'.
     *
     * @access protected
     * @param  string $fieldType   The internal field type
     * @return string              The QuickForm element type name
     */
    function _getQFType($fieldType)
    {
        if (isset($this->_elementTypeMap[$fieldType])) {
            return $this->_elementTypeMap[$fieldType];
        }
        return 'text';
    }
}

?>
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
 * <ul>
 *  <li>linkDisplayFields:
 *   These fields will be used when displaying a record in a select box. The fields listed
 *   will be seperated by ", ". If you specify a link field as a display field and linkDisplayLevel
 *   is not 0, the link will be followed and the display fields of the record linked to
 *   displayed within parenthesis.
 *   
 *   For example, say we have these tables:
 *   <code>
 *    [person]
 *    name = 130
 *    gender_id = 129
 *
 *    [gender]
 *    id = 129
 *    name = 130
 *   </code>
 *   this link:
 *   <code>
 *    [person]
 *    gender_id = gender:id
 *   </code>
 *   and this data:
 *   Person:
 *    name: "Justin Patrin"
 *    gender_id: 1
 *   Gender:
 *    id: 1
 *    name: "male"
 *   
 *   If person's display fields are:
 *   <code>
 *    var $fb_linkDisplayFields = array('name', 'gender_id');
 *    and gender's display fields are: 
 *    var $fb_linkDisplayFields = array('name');
 *   </code>
 *   and we set linkDisplayLevel to 0, the person record will be displayed as:
 *   "Justin Patrin, 1"
 *   
 *   If we set linkDisplayLevel to 1, the person record will be displayed as:
 *   "Justin Patrin, (male)"
 *  </li>
 *  <li>linkOrderFields:
 *   The field to be used for sorting the options of an auto-generated
 *   select element. Can be overridden individually by a similarly-named
 *   public class property or DataObject property.
 *  </li>
 *  <li>dbDateFormat:
 *   This is for the future support of string date formats other than ISO, but
 *   currently, that's the only supported one. Set to 1 for ISO, other values
 *   may be available later on.
 *  </li>
 *  <li>dateElementFormat:
 *   A format string that represents the display settings for QuickForm date elements.
 *   Example: "d-m-Y". See QuickForm documentation for details on format strings.
 *   Legal letters to use in the format string that work with FormBuilder are:
 *   d,m,Y,H,i,s
 *  </li>
 *  <li>timeElementFormat:
 *   A format string that represents the display settings for QuickForm time elements.
 *   Example: "H:i:s". See QuickForm documentation for details on format strings.
 *  </li>
 *  <li>hidePrimaryKey:
 *   By default, hidden fields are generated for the primary key of a DataObject.
 *   This behaviour can be deactivated by setting this option to 0.
 *  </li>
 *  <li>createSubmit:
 *   If set to 0, no submit button will be created for your forms. Useful when
 *   used together with QuickForm_Controller when you already have submit buttons
 *   for next/previous page. By default, a button is being generated.
 *  </li>
 *  <li>submitText:
 *   The caption of the submit button, if created.
 *  </li>
 *  <li>dateFieldLanguage:
 *   The language to be used in date fields (see HTML_QuickForm documentation on
 *   the date element for more details). This option is the only one that cannot be
 *   overridden in one of your classes.
 *  </li>
 *  <li>linkDisplayLevel:
 *   If this is set to 1 or above, links will be followed in the display fields and
 *   display the display fields of the record linked to. If this is set to 2, links
 *   will be followed in the linked record as well. This can be set to any number of
 *   links you wish but could easily slow down your application if set to more than
 *   1 or 2.
 *  </li>
 * </ul>
 * All the settings for FormBuilder must be in a section [DB_DataObject_FormBuilder]
 * within the DataObject.ini file (or however you've named it).
 * If you stuck to the DB_DataObject example in the doc, you'll read in your
 * config like this:
 * <code>
 *  $config = parse_ini_file('DataObject.ini', true);
 *  foreach ($config as $class => $values) {
 *      $options = &PEAR::getStaticProperty($class, 'options');
 *      $options = $values;
 *  }
 * </code>
 * Unfortunately, DataObject will overwrite FormBuilder's settings when first instantiated,
 * so you'll have to add another line after that:
 * <code>
 *  $_DB_DATAOBJECT_FORMBUILDER['CONFIG'] = $config['DB_DataObject_FormBuilder'];
 * </code>
 * Now you're ready to go!
 *
 * There are some more settings that can be set individually by altering
 * some special properties of your DataObject-derived classes.
 * These special properties are as follows:
 * <ul>
 *  <li>fb_preDefElements:
 *   Array of user-defined QuickForm elements that will be used
 *   for the field matching the array key. If no match is found,
 *   the element for that field will be auto-generated.
 *   Make your element objects either in the constructor or in
 *   the fb_getForm() method, before the _generateForm() method is
 *   called. Use HTML_QuickForm::createElement() to do this.
 *  </li>
 *  <li>fb_preDefOrder:
 *   Indexed array of element names. If defined, this will determine the order
 *   in which the form elements are being created. This is useful if you're using
 *   QuickForm's default renderer or dynamic templates and the order of the fields
 *   in the database doesn?t match your needs.
 *  </li>
 *  <li>fb_fieldLabels:
 *   Array of field labels. The key of the element represents the field name.
 *   Use this if you want to keep the auto-generated elements, but still define
 *   your own labels for them.
 *  </li>
 *  <li>fb_fieldsToRender:
 *   Array of fields to render elements for. If a field is not given, it will not be rendered.
 *  </li>
 *  <li>fb_userEditableFields:
 *   Array of fields which the user can edit. If a field is rendered but not specified in this array,
 *   it will be frozen. Ignored if not given.
 *  </li>
 *  <li>fb_dateFields:
 *   A simple array of field names indicating which of the fields in a particular table/class
 *   are actually to be treated date fields.
 *   This is an unfortunate workaround that is neccessary because the DataObject
 *   generator script does not make a difference between any other datatypes than
 *   string and integer. When it does, this can be dropped.
 *  </li>
 *  <li>fb_timeFields:
 *   A simple array of field names indicating which of the fields in a particular table/class
 *   are actually to be treated time fields.
 *  </li>
 *  <li>fb_textFields:
 *   A simple array of field names indicating which of the fields in a particular table/class
 *   are actually to be treated as textareas.
 *   This is an unfortunate workaround that is neccessary because the DataObject
 *   generator script does not make a difference between any other datatypes than
 *   string and integer. When it does, this can be dropped.</li>
 *  <li>fb_crossLinks:
 *   The crossLinks array holds data pertaining to many-many links. If you have a table which
 *   links two tables together, you can use this to automatically create a set of checkboxes
 *   on your form. The simplest way of using this is:
 *   <code>
 *    $crossLinks = array(array('table' => 'crossLinkTable'));
 *   </code>
 *   Where crossLinkTable is the name of the linking table. You can have as many cross-link
 *   entries as you want. Try it with just the table ewntry first. If it doesn't work, you
 *   can specify the fields to use as well.
 *   <code>
 *    'fromField' => 'linkFieldToCurrentTable' //This is the field which links to the current (from) table
 *    'toField' => 'linkFieldToLinkedTable' //This is the field which links to the "other" (to) table
 *   </code>
 *  </li>
 *  <li>fb_tripleLinks:
 *   The tripleLinks array can be used to display checkboxes for "triple-links". A triple link is set
 *   up with a table which links to three different tables. These will show up as a table of checkboxes
 *   The initial setting (table) is the same as for crossLinks. The field configuration keys (if you
 *   need them) are:
 *   <code>
 *    'fromField'
 *    'toField1'
 *    'toField2'
 *   </code>
 *  </li>
 *  <li>fb_selectAddEmpty:
 *   An array of the link fields which should have an empty option added to the select box.
 *   This is only a valid option for fields which link to another table.
 *  </li>
 * </ul>
 * In addition, there are special methods you can define in your DataObject classes for even more control.
 * <ul>
 *  <li>preGenerateForm():
 *   This method will be called before the form is generated. Use this to change property values or options
 *   in your DataObject. This is where you should set up fb_preDefElements.
 *  </li>
 *  <li>postGenerateForm(&$form):
 *   This method will be called after the form is generated. The form is passed in by reference so you can
 *   alter it. Use this method to add, remove, or alter elements in the form or the form itself.
 *  </li>
 *  <li>preProcessForm(&$values):
 *   This method is called just before FormBuilder processes the submitted form data. The values are sent
 *   by reference in the first parameter as an associative array. The key is the element name and the value
 *   the submitted value. You can alter the values as you see fit (md5 on passwords, for example).
 *  </li>
 *  <li>postProcessForm(&$values):
 *   This method is called just after FormBuilder processed the submitted form data. The values are again
 *   sent by reference. This method could be used to inform the user of changes, alter the DataObject, etc.
 *  </li>
 *  <li>getForm():
 *   If this function exists, it will be used instead of FormBuilder's internal form generation routines
 *   Use this only if you want to create the entire form on your own.
 *  </li>
 * </ul>
 *
 * Note for PHP5-users: These properties have to be public! In general, you can
 * override all settings from the .ini file by setting similarly-named properties
 * in your DataObject classes.
 *
 * <b>Most basic usage:</b>
 * <code>
 *  $do =& new MyDataObject();
 *  // Insert "$do->get($some_id);" here to edit an existing object instead of making a new one
 *  $fg =& DB_DataObject_FormBuilder::create($do);
 *  $form =& $fg->getForm();
 *  if ($form->validate()) {
 *      $form->process(array(&$fg,'processForm'), false);
 *      $form->freeze();
 *  }
 *  $form->display();
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
require_once ('DB/DataObject.php');

// Constants used for forceQueryType()
define ('DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT',    0);
define ('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEINSERT',   1);
define ('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCEUPDATE',   2);
define ('DB_DATAOBJECT_FORMBUILDER_QUERY_FORCENOACTION', 3);

// Constants used for cross/triple links
define ('DB_DATAOBJECT_FORMBUILDER_CROSSLINK',  1048576);
define ('DB_DATAOBJECT_FORMBUILDER_TRIPLELINK', 2097152);
define ('DB_DATAOBJECT_FORMBUILDER_ENUM',       4194304);

// Error code constants
define ('DB_DATAOBJECT_FORMBUILDER_ERROR_UNKNOWNDRIVER', 4711);
define ('DB_DATAOBJECT_FORMBUILDER_ERROR_NODATAOBJECT',  4712);

class DB_DataObject_FormBuilder
{
    /**
     * Add a header to the form - if set to true, the form will
     * have a header element as the first element in the form.
     *
     * @access public
     * @see formHeaderText
     */
    var $addFormHeader = true;

    /**
     * Text for the form header. If not set, the name of the database
     * table this form represents will be used.
     *
     * @access public
     * @see addFormHeader
     */
    var $formHeaderText = null;

    /**
     * Text that is displayed as an error message if a validation rule
     * is violated by the user's input. Use %s to insert the field name.
     *
     * @access public
     * @see requiredRuleMessage
     */
    var $ruleViolationMessage = '%s: The value you have entered is not valid.';
    
    /**
     * Text that is displayed as an error message if a required field is
     * left empty. Use %s to insert the field name.
     *
     * @access public
     * @see ruleViolationMessage
     */
    var $requiredRuleMessage = 'The field %s is required.';

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
     * @see HTML_QuickForm_date
     */
    var $dateFieldLanguage = 'en';
    
    /**
     * Callback method to convert a date from the format it is stored
     * in the database to the format used by the QuickForm element that
     * handles date values. Must have a format usable with call_user_func().
     */
    var $dateFromDatabaseCallback = array('DB_DataObject_FormBuilder','_date2array');
    
    /**
     * Callback method to convert a date from the format used by the QuickForm
     * element that handles date values to the format the database can store it in. 
     * Must have a format usable with call_user_func().
     */
    var $dateToDatabaseCallback = array('DB_DataObject_FormBuilder','_array2date');
    
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
     //FF ## MODIFIED/ADDED ## FF//
    var $elementTypeMap = array('shorttext' => 'text',
                                'longtext'  => 'textarea',
                                'date'      => 'date',
                                'time'      => 'time',
                                'integer'   => 'text',
                                'float'     => 'text');

    /**
     * An array of the default fields to display when creating a select box
     */
    var $linkDisplayFields;

    /**
     * An array of the default fields to order records by when creating a select box
     */
    var $linkOrderFields;

    /**
     * Text for submit button
     */
    var $submitText = 'Submit';

    var $createSubmit = true;
    var $fieldLabels;
    var $fieldsToRender;
    var $userEditableFields;
    var $preDefGroups;
    var $preDefOrder;
    var $preDefElements;
    var $selectAddEmpty;
    var $hidePrimaryKey = true;
    var $textFields = array();
    var $dateFields = array();
    //FF ## ADDED ## FF//
    var $timeFields = array();
    var $linkElementTypes = array();
    var $enumFields = array();

    /**
     * Holds cross link data.
     *   The crossLinks array holds data pertaining to many-many links. If you have a table which
     *   links two tables together, you can use this to automatically create a set of checkboxes
     *   on your form. The simplest way of using this is:
     *   <code>
     *    $crossLinks = array(array('table' => 'crossLinkTable'));
     *   </code>
     *   Where crossLinkTable is the name of the linking table. You can have as many cross-link
     *   entries as you want. Try it with just the table ewntry first. If it doesn't work, you
     *   can specify the fields to use as well.
     *   <code>
     *    'fromField' => 'linkFieldToCurrentTable' //This is the field which links to the current (from) table
     *    'toField' => 'linkFieldToLinkedTable' //This is the field which links to the "other" (to) table
     *   </code>
     */
    var $crossLinks;

    /**
     * Holds triple link data.
     *   The tripleLinks array can be used to display checkboxes for "triple-links". A triple link is set
     *   up with a table which links to three different tables. These will show up as a table of checkboxes
     *   The initial setting (table) is the same as for crossLinks. The field configuration keys (if you
     *   need them) are:
     *   <code>
     *    'fromField'
     *    'toField1'
     *    'toField2'
     *   </code>
     */
    var $tripleLinks;

    /**
     * DB_DataObject_FormBuilder::create()
     *
     * Factory method. As this is meant as an abstract class, it is the only supported
     * method to make a new object instance. Pass the DataObject-derived class you want to
     * build a form from as the first parameter. Use the second to pass additional options.
     *
     * Options can be:
     * - 'ruleViolationMessage' : See description of similarly-named class property
     * - 'requiredRuleMessage' : See description of similarly-named class property
     * - 'addFormHeader' : See description of similarly-named class property
     * - 'formHeaderText' : See description of similarly-named class property
     *
     * The third parameter is the name of a driver class. A driver class will take care of
     * the actual form generation. This way it's possible to have FormBuilder build different
     * forms for different types of output media from the same set of DataObjects.
     *
     * Currently available driver classes:
     * - QuickForm (stable)
     * - XUL (experimental!)
     *
     * @param object $do      The DB_DataObject-derived object for which a form shall be built
     * @param array $options  An optional associative array of options.
     * @param string $driver  Optional: Name of the driver class for constructing the form object. Default: QuickForm.
     * @access public
     * @returns object        DB_DataObject_FormBuilder or PEAR_Error object
     */
    function &create(&$do, $options = false, $driver = 'QuickForm')
    {
        if (!is_a($do, 'db_dataobject')) {
            $err =& PEAR::raiseError('DB_DataObject_FormBuilder::create(): Object does not extend DB_DataObject.',
                                     DB_DATAOBJECT_FORMBUILDER_ERROR_NODATAOBJECT);
            return $err;
        }
        
        @include_once('DB/DataObject/FormBuilder/'.$driver.'.php');
        $className = 'db_dataobject_formbuilder_'.strtolower($driver);
        if (class_exists($className)) {
            $obj = &new $className($do, $options);
            return $obj;
        }
        $err =& PEAR::raiseError("DB_DataObject_FormBuilder::create(): Driver class '$className' not found.",
                                 DB_DATAOBJECT_FORMBUILDER_ERROR_UNKNOWNDRIVER);
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
    function DB_DataObject_FormBuilder(&$do, $options = false)
    {
        // Set default callbacks first!
        $this->dateToDatabaseCallback = array($this, '_array2date');
        $this->dateFromDatabaseCallback = array($this, '_date2array');
        
        // Read in config
        if (is_array($options)) {
            $vars = get_object_vars($this);
            reset($options);
            while (list($key, $value) = each($options)) {
                if (in_array($key, $vars) && $key[0] != '_') {
                    $this->$key = $value;
                }
            }
        }

        //read all config options into member vars
        foreach ($GLOBALS['_DB_DATAOBJECT_FORMBUILDER']['CONFIG'] as $var => $value) {
            $this->$var = $value;
        }
        
        // Default mappings from global field types to QuickForm element types
        //FF ## MODIFIED/ADDED ## FF//
        foreach(array('elementTypeMap',
                      'linkDisplayFields',
                      'linkOrderFields',
                      'preDefOrder',
                      'textFields',
                      'dateFields',
                      'timeFields',
                      'preDefGroups',
                      'fieldLabels',
                      'fieldsToRender',
                      'userEditableFields',
                      'linkElementTypes') as $member) {
            if (isset($this->$member)) {
                if (is_string($this->$member)) {
                    $this->$member = $this->_explodeArrString($this->$member);
                }
            }
        }
        $this->_do = &$do;
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

        // Retrieve the form object to use (may depend on the current renderer)
        $form =& $this->_createFormObject($formName, $method, $action, $target);

        // Initialize array with default values
        $formValues = $this->_do->toArray();

        // Add a header to the form - set addFormHeader property to false to prevent this
        $this->_addFormHeader($form);

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
            $elements_to_freeze = array();
        }

        $links = $this->_do->links();
        if (isset($this->_do->_primary_key)) {
            $pk = $this->_do->_primary_key;
        } else {
            $k = $this->_do->keys();
            $pk = $k[0];
        }
        foreach ($elements as $key => $type) {
            // Check if current field is primary key. And primary key hiding is on. If so, make hidden field
            if (in_array($key, $keys) && $this->hidePrimaryKey === true) {
                $element =& $this->_createHiddenField($key);
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
                    // Use predefined form field, IMPORTANT: This may depend on the used renderer!!
                    $element =& $this->preDefElements[$key];
                } elseif (is_array($links) && isset($links[$key])) {
                    // If this field links to another table, display selectbox or radiobuttons
                    $opt = $this->getSelectOptions($key);
                    if (isset($this->linkElementTypes[$key]) && $this->linkElementTypes[$key] == 'radio') {
                        $element =& $this->_createRadioButtons($key, $opt);
                    } else {
                        $element =& $this->_createSelectBox($key, $opt);
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
                        $element =& $this->_createIntegerField($key);
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
                        $element =& $this->_createTextArea($key);
                    }
                    break;
                case ($type & DB_DATAOBJECT_STR):
                    // If field content contains linebreaks, make textarea - otherwise, standard textbox
                    if (!empty($this->_do->$key) && strstr($this->_do->$key, "\n")) {
                        $element =& $this->_createTextArea($key);
                    } elseif (!isset($element)) {
                        $element =& $this->_createTextField($key);
                    }
                    break;
                case ($type & DB_DATAOBJECT_FORMBUILDER_CROSSLINK):
                    unset($element);
                    //$this->_createGroup($form, $key);
                    // generate crossLink stuff
                    // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
                    /*if (isset($this->crossLinks) && is_array($this->crossLinks)) {
                     // primary key detection taken from getSelectOptions() so it doesn't allow
                     // the use of multiple keys... this should be improved in the future if possible imho..
            if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }*/
                    if (empty($pk)) {
                        return PEAR::raiseError('A primary key must exist in the base table when using crossLinks.');
                    }
                    //foreach ($this->crossLinks as $crossLink) { //TODO
                    $crossLink = $this->crossLinks[$key];
                    $groupName  = '__crossLink_' . $crossLink['table'];
                    //if ($form->elementExists($groupName)) {
                    //$linkGroup =& $form->getElement($groupName);
                    $crossLinksDo = DB_DataObject::factory($crossLink['table']);
                    if (PEAR::isError($crossLinksDo)) {
                        die($crossLinksDo->getMessage());
                    }
                    
                    $crossLinksLinks = $crossLinksDo->links();
                    
                    list($linkedtable, $linkedfield) = explode(':', $crossLinksLinks[$crossLink['toField']]);
                    $all_options      = $this->_getSelectOptions($linkedtable);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $crossLinksDo->{$crossLink['fromField']} = $this->_do->$pk;
                        if ($crossLinksDo->find() > 0) {
                            while ($crossLinksDo->fetch()) {
                                $selected_options[] = $crossLinksDo->{$crossLink['toField']};
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
                    $element = array();
                    foreach ($all_options as $key=>$value) {
                        /*$crossLinksElement = HTML_QuickForm::createElement('checkbox', $groupName.'[]', null, $value);
                        $crossLinksElement->updateAttributes(array('value' => $key));
                        if (in_array($key, $selected_options)) {
                            $crossLinksElement->setChecked(true);
                        }*/
                        $crossLinksElement = $this->_createCheckbox($groupName.'[]', $value, $key, in_array($key, $selected_options));
                        $element[] = $crossLinksElement;
                    }
                    $this->_addElementGroupToForm($this->_form, $element, $groupName, '<br/>');
                    unset($element);
                    //$groupLabel = $this->getFieldLabel($groupName);
                    //$linkGroup->setLabel($groupLabel);
                    //$linkGroup->setElements($grp);
                    //}
                    //}
                    //}
                    //}
                    break;
                case ($type & DB_DATAOBJECT_FORMBUILDER_TRIPLELINK):
                    unset($element);
                    //$element =& $this->_createStaticField($key);
                    // generate tripleLink stuff
                    // be sure to use the latest DB_DataObject version from CVS (there's a bug in the latest DBO release 1.5.3)
                    //if (isset($this->tripleLinks) && is_array($this->tripleLinks)) {
                    // primary key detection taken from getSelectOptions() so it doesn't allow
                    // the use of multiple keys... this should be improved in the future if possible imho..
                    /*if (isset($this->_do->_primary_key)) {
                $pk = $this->_do->_primary_key;
            } else {
                $k = $this->_do->keys();
                $pk = $k[0];
            }*/
                    if (empty($pk)) {
                        return PEAR::raiseError('A primary key must exist in the base table when using tripleLinks.');
                    }
                    //foreach ($this->tripleLinks as $tripleLink) { //TODO
                    $tripleLink = $this->tripleLinks[$key];
                    $elName  = '__tripleLink_' . $tripleLink['table'];
                    //if ($form->elementExists($elName)) {
                    $freeze = array_search('__tripleLink_' . $tripleLink['table'], $elements_to_freeze);
                    $tripleLinkDo = DB_DataObject::factory($tripleLink['table']);
                    if (PEAR::isError($tripleLinkDo)) {
                        die($tripleLinkDo->getMessage());
                    }
                    
                    $tripleLinksLinks = $tripleLinkDo->links();
                    
                    $fromField = $tripleLink['fromField'];
                    $toField1 = $tripleLink['toField1'];
                    $toField2 = $tripleLink['toField2'];
                    
                    list($linkedtable1, $linkedfield1) = explode(':', $tripleLinksLinks[$toField1]);
                    list($linkedtable2, $linkedfield2) = explode(':', $tripleLinksLinks[$toField2]);
                    
                    $all_options1 = $this->_getSelectOptions($linkedtable1);
                    $all_options2 = $this->_getSelectOptions($linkedtable2);
                    $selected_options = array();
                    if (!empty($this->_do->$pk)) {
                        $tripleLinkDo->$fromField = $this->_do->$pk;
                        if ($tripleLinkDo->find() > 0) {
                            while ($tripleLinkDo->fetch()) {
                                $selected_options[$tripleLinkDo->$toField1][] = $tripleLinkDo->$toField2;
                            }
                        }
                    }
                    
                    // THIS IS PROBLEMATIC WHEN USED WITH CUSTOM RENDERERS THAT DO NOT OUTPUT HTML
                    include_once ('HTML/Table.php');
                    $tripleLinkTable = new HTML_Table();
                    $tripleLinkTable->setAutoGrow(true);
                    $tripleLinkTable->setAutoFill('');
                    $row = 0;
                    $col = 0;
                    foreach ($all_options2 as $key2=>$value2) {
                        $col++;
                        $tripleLinkTable->setCellContents($row, $col, $value2);
                        $tripleLinkTable->setCellAttributes($row, $col, array('style' => 'text-align: center'));
                    }
                    foreach ($all_options1 as $key1=>$value1) {
                        $row++;
                        $col = 0;
                        $tripleLinkTable->setCellContents($row, $col, $value1);
                        foreach ($all_options2 as $key2=>$value2) {
                            $col++;
                            $tripleLinksElement = $this->_createCheckbox('__tripleLink_' . $tripleLink['table'] . '[' . $key1 . '][]',
                                                                        '',
                                                                        $key2,
                                                                        is_array($selected_options[$key1]) && in_array($key2, $selected_options[$key1]),
                                                                        $freeze);
                            /*$element = HTML_QuickForm::createElement('checkbox', '__tripleLink_' . $tripleLink['table'] . '[' . $key1 . '][]', null, null);
                            $element->updateAttributes(array('value' => $key2));
                            if ($freeze) {
                                $element->freeze();
                            }
                            if (is_array($selected_options[$key1])) {
                                if (in_array($key2, $selected_options[$key1])) {
                                    $element->setChecked(true);
                                }
                            }*/
                            $tripleLinkTable->setCellContents($row, $col, $tripleLinksElement->toHTML());
                            $tripleLinkTable->setCellAttributes($row, $col, array('style' => 'text-align: center'));
                        }
                    }
                    $hrAttrs = array('bgcolor' => 'lightgrey');

                    $tripleLinkTable->setRowAttributes(0, $hrAttrs, true);
                    $tripleLinkTable->setColAttributes(0, $hrAttrs);
                    //$elLabel = $this->getFieldLabel($elName);
                    //$linkElement =& $form->getElement($elName);
                    //$linkElement->setLabel($elLabel);
                    //$linkElement->setValue($tripleLinkTable->toHTML());
                    $element =& $this->_createStaticField($elName, $tripleLinkTable->toHTML());
                    //}
                    //}
                    //}
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
                                $element =& $this->_createRadioButtons($key, $options);
                            }
                        } else {
                            $element =& $this->_createSelectBox($key, $options);
                        }
                        unset($options);
                    }
                    break;
                default:
                    if (!isset($element)) {
                        $element =& $this->_createTextField($key);
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
                    $this->_addElementGroupToForm($form, $element, $key);
                } else {
                    $this->_addElementToForm($form, $element);
                }
            } // End if
            
            
            //ADD REQURED RULE FOR NOT_NULL FIELDS
            if ((!in_array($key, $keys) || $this->hidePrimaryKey === false)
                && ($type & DB_DATAOBJECT_NOTNULL)
                && !in_array($key, $elements_to_freeze)) {
                $this->_setFormElementRequired($form, $key);
            }

            // VALIDATION RULES
            $this->_addFieldRulesToForm($form, $rules, $key);
        } // End foreach

        // Freeze fields that are not to be edited by the user
        $this->_freezeFormElements($form, $elements_to_freeze);
        
        //GROUP SUBMIT
        $flag = true;
        if (isset($groupelements) && in_array('__submit__', $groupelements)) {
            $group = $this->preDefGroups['__submit__'];
            if (count($groups[$group]) > 1) {
                $groups[$group][] =& $this->_createSubmitButton();
                $flag = false;
            } else {
                $flag = true;
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

    
    /**
     * DB_DataObject_FormBuilder::_exlplodeArrString()
     *
     * Internal method, will convert string representations of arrays as used in .ini files
     * to real arrays. String format example:
     * key1:value1,key2:value2,key3:value3,...
     *
     * @param string $str The string to convert to an array
     * @access protected
     * @return array
     */
    function _explodeArrString($str) {
        $ret = array();
        $arr = explode(',', $str);
        foreach ($arr as $mapping) {
            if (strstr($mapping, ':')) {
                $map = explode(':', $mapping);
                $ret[$map[0]] = $map[1];   
            } else {
                $ret[] = $mapping;
            }
        }
        return $ret;
    }


    /**
     * DB_DataObject_FormBuilder::_reorderElements()
     *
     * Changes the order in which elements are being processed, so that
     * you can use QuickForm's default renderer or dynamic templates without
     * being dependent on the field order in the database.
     *
     * Make a class property named "fb_preDefOrder" in your DataObject-derived classes
     * which contains an array with the correct element order to use this feature.
     *
     * @return mixed  Array in correct order or FALSE if reordering was not possible
     * @access protected
     * @author Fabien Franzen <atelierfabien@home.nl>
     */
    function _reorderElements() {
        if (isset($this->preDefOrder) && is_array($this->preDefOrder)) {
            $this->debug("<br/>...reordering elements...<br/>");
            $elements = $this->_getFieldsToRender();
            $table = $this->_do->table();
            $crossLinks = $this->_getCrossLinkElementNames();

            foreach ($this->preDefOrder as $elem) {
                if (isset($elements[$elem])) {
                    $ordered[$elem] = $elements[$elem]; //key=>type
                } elseif (!isset($table[$elem]) && !isset($crossLinks[$elem])) {
                    $this->debug('<br/>...reorder not supported: invalid element(key) found "'.$elem.'"...<br/>');
                    return false;
                }
            }
            return $ordered;
        } else {
            $this->debug('<br/>...reorder not supported, fb_preDefOrder is not set or is not an array...<br/>');
            return false;
        }
    }

    function _getCrossLinkElementNames() {
        $ret = array();
        if (isset($this->tripleLinks)) {
            foreach ($this->tripleLinks as $tripleLink) {
                $ret['__tripleLink_'.$tripleLink['table']] = DB_DATAOBJECT_FORMBUILDER_TRIPLELINK;
            }
        }
        if ($this->crossLinks) {
            foreach ($this->crossLinks as $crossLink) {
                $ret['__crossLink_'.$crossLink['table']] = DB_DATAOBJECT_FORMBUILDER_CROSSLINK;
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
    function useForm(&$form, $append = false)
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
        if (isset($this->fieldLabels[$fieldName])) {
            return $this->fieldLabels[$fieldName];
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
     * 1) $displayFields parameter<br/>
     * 2) databaseName.formBuilder.ini file, section [tableName__linkDisplayFields]<br/>
     * 3) the fb_linkDisplayFields member variable of the dataobject<br/>
     * 4) global 'linkDisplayFields' setting for DB_DataObject_FormBuilder
     *
     *
     * @param DB_DataObject the dataobject to get the display value for, must be populated
     * @param mixed field to use to display, may be an array with field names or a single field
     * @return string select display value for this field
     * @access public
     */
    function getDataObjectSelectDisplayValue(&$do, $displayFields = false, $level = 1) {
        $links = $do->links();
        if ($displayFields === false) {
            if (isset($do->fb_linkDisplayFields)) {
                $displayFields = $do->fb_linkDisplayFields;
            } elseif (isset($this->linkDisplayFields) && $this->linkDisplayFields){
                $displayFields = $this->linkDisplayFields;
            }
            if ($displayFields === null) {
                $displayFields = array($pk);
            }
        }
        $ret = '';
        $first = true;
        foreach ($displayFields as $field) {
            if ($first) {
                $first = false;
            } else {
                $ret .= ', ';
            }
            if (isset($do->$field)) {
                if ($this->linkDisplayLevel > $level && isset($links[$field])
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
     * the linked object's property "fb_linkDisplayFields". If that one is not present,
     * it will try to use the global configuration setting "linkDisplayFields".
     * Can also be called with a second parameter containing the name of the display
     * field - this will override all other settings.
     * Same goes for "linkOrderFields", which determines the field name used for
     * sorting the option elements. If neither a config setting nor a class property
     * of that name is set, the display field name will be used.
     *
     * @param string $field         The field to fetch the links from. You should make sure the field actually *has* links before calling this function (see: DB_DataObject::links())
     * @param string $displayFields  (Optional) The name of the field used for the display text of the options
     * @return array strings representing all of the records in the table $field links to.
     * @access public
     */
    function getSelectOptions($field, $displayFields = false)
    {
        if (empty($this->_do->_database)) {
            // TEMPORARY WORKAROUND !!! Guarantees that DataObject config has
            // been loaded and all link information is available.
            $this->_do->keys();   
        }
        $links = $this->_do->links();
        $link = explode(':', $links[$field]);

        $res = $this->_getSelectOptions($link[0],
                                        $displayFields,
                                        isset($this->selectAddEmpty) && in_array($field, $this->selectAddEmpty));

        if ($res !== false) {
            return $res;
        }

        $this->debug('Error: '.get_class($opts).' does not inherit from DB_DataObject');
        return array();
    }

    /**
     * Internal function to get the select potions for a table.
     *
     * @param string $table The table to get the select display strings for.
     * @param array $displayFields array of diaply fields to use. Will default to the FB or DO options.
     * @param bool $selectAddEmpty If set to true, there will be an empty option in the returned array.
     *
     * @return array strings representing all of the records in $table.
     * @access protected
     */
    function _getSelectOptions($table, $displayFields = false, $selectAddEmpty = false) {
        $opts = DB_DataObject::factory($table);
        if (is_a($opts, 'db_dataobject')) {
            if (isset($opts->_primary_key)) {
                $pk = $opts->_primary_key;
            } else {
                $k = $opts->keys();
                $pk = $k[0];
            }
            if ($displayFields === false) {
                if (isset($opts->fb_linkDisplayFields)) {
                    $displayFields = $opts->fb_linkDisplayFields;
                } elseif (isset($this->linkDisplayFields) && $this->linkDisplayFields){
                    $displayFields = $this->linkDisplayFields;
                } else {
                    $displayFields = array($pk);
                }
            }

            if (isset($opts->fb_linkOrderFields)) {
                $orderFields = $opts->fb_linkOrderFields;
            } elseif (isset($this->linkOrderFields) && $this->linkOrderFields !== null){
                $orderFields = $this->linkOrderFields;
            } else {
                $orderFields = $displayFields;
            }
            $orderStr = '';
            $first = true;
            foreach ($orderFields as $col) {
                if ($first) {
                    $first = false;
                } else {
                    $orderStr .= ', ';
                }
                $orderStr .= $col;
            }
            if ($orderStr) {
                $opts->orderBy($orderStr);
            }
            $list = array();
                
            // FIXME!
            if ($selectAddEmpty) {
                $list[''] = '';
            }
            
            // FINALLY, let's see if there are any results
            if ($opts->find() > 0) {
                while ($opts->fetch()) {
                    $list[$opts->$pk] = $this->getDataObjectSelectDisplayValue($opts, $displayFields);
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
     * there and add them to the "fb_preDefElements" property, so they will not be auto-generated.
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
    function &getForm($action = false, $target = '_self', $formName = false, $method = 'post')
    {
        if (method_exists($this->_do, 'pregenerateform')) {
            $this->_do->preGenerateForm($this);
        }
        $badVars = array('linkDisplayFields', 'linkOrderFields');
        foreach (get_object_vars($this) as $var => $value) {
            if ($var[0] != '_' && !in_array($var, $badVars) && isset($this->_do->{'fb_'.$var})) {
                $this->$var = $this->_do->{'fb_'.$var};
            }
        }
        if(isset($this->crossLinks) && is_array($this->crossLinks)) {
            foreach ($this->crossLinks as $key => $crossLink) {
                $groupName  = '__crossLink_' . $crossLink['table'];
                $do = DB_DataObject::factory($crossLink['table']);
                if (PEAR::isError($do)) {
                    return PEAR::raiseError('Cannot load dataobject for table '.$crossLink['table'].' - '.$do->getMessage());
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
                unset($this->crossLinks[$key]);
                $this->crossLinks[$groupName] = array('table' => $crossLink['table'],
                                                      'fromField' => $fromField,
                                                      'toField' => $toField);
                //}
            }
        }
        if (isset($this->tripleLinks) && is_array($this->tripleLinks)) {
            foreach ($this->tripleLinks as $key => $tripleLink) {
                $elName  = '__tripleLink_' . $tripleLink['table'];
                //$freeze = array_search($elName, $elements_to_freeze);
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
                unset($this->tripleLinks[$key]);
                $this->tripleLinks[$elName] = array('table' => $tripleLink['table'],
                                                    'fromField' => $fromField,
                                                    'toField1' => $toField1,
                                                    'toField2' => $toField2);
            }
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
     * your format string (see "dateElementFormat" option). If you want to enter a time as well,
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
     * If second parameter is true, it will return a unix timestamp instead. //FRANK: Not at this point it wont
     *
     * Beware: For the date conversion to work, you must at least use the letters "d", "m" and "Y" in
     * your format string (see "dateElementFormat" option). If you want to enter a time as well,
     * you will have to use "H", "i" and "s" as well. Other letters will not work! Exception: You can
     * also use "M" instead of "m" if you want plain text month names.
     *
     * @param array $date   An array representation of a date, as user in HTML_QuickForm's date element
     * @param boolean $timestamp  Optional. If true, return a timestamp instead of a string. Defaults to false.
     * @return mixed
     * @access protected
     */
     //FF ## MODIFY/EDIT/ADDED (make usable for time only too) ## FF//
    function _array2date($dateInput, $timestamp = false)
    {
        if (isset($dateInput['M'])) {
            $month = $dateInput['M'];
        } elseif (isset($dateInput['m'])) {
            $month = $dateInput['m'];   
        }
        $strDate = "";
        if (isset($dateInput['Y']) && isset($month) && isset($dateInput['d'])) {
            $strDate .= sprintf('%s-%s-%s', $dateInput['Y'], $month, $dateInput['d']);
        }
        if (isset($dateInput['H']) && isset($dateInput['i']) && isset($dateInput['s'])) {
            if (!empty($strDate)) {
                $strDate .= " ";
            }
            $strDate .= sprintf('%s:%s:%s', $dateInput['H'], $dateInput['i'], $dateInput['s']);
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
     * If a method named "preProcessForm()" exists in your derived class, it will be called before
     * processForm() starts doing its magic. The data that has been submitted by the form
     * will be passed to that method as a parameter.
     * Same goes for a method named "postProcessForm()", with the only difference - you might
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
        if (method_exists($this->_do, 'preprocessform')) {
            $this->_do->preProcessForm($values);
        }
        
        $editableFields = $this->_getUserEditableFields();
        $tableFields = $this->_do->table();

        foreach ($values as $field => $value) {
            $this->debug("Field $field ");
            // Double-check if the field may be edited by the user... if not, don't
            // set the submitted value, it could have been faked!
            if (in_array($field, $editableFields)) {
                if (isset($tableFields[$field])) {
                    if (($tableFields[$field] & DB_DATAOBJECT_DATE) || in_array($field, $this->dateFields)) {
                        $this->debug("DATE CONVERSION for using callback from $value ...");
                        $value = call_user_func($this->dateToDatabaseCallback, $value);
                    //FF ## ADDED ## FF//
                    } elseif (($tableFields[$field] & DB_DATAOBJECT_TIME) || in_array($field, $this->timeFields)) {
                        $this->debug("TIME CONVERSION for using callback from $value ...");
                        $value = call_user_func($this->dateToDatabaseCallback, $value);
                    } elseif (is_array($value)) {
                        if (isset($value['tmp_name'])) {
                            $this->debug(" (converting file array) ");
                            $value = $value['name'];
                        //JUSTIN
                        //This is not really a valid assumption IMHO. This should only be done if the type is
                        // date or the field is in dateFields
                        /*} else {
                            $this->debug("DATE CONVERSION using callback from $value ...");
                            $value = call_user_func($this->dateToDatabaseCallback, $value);*/
                        }
                    }
                    $this->debug('is substituted with "'.print_r($value, true)."\".\n");
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

        if (isset($this->_do->primary_key)) {
            $pk = $this->_do->primary_key;
        } else {
            $keys = $this->_do->keys();
            if (is_array($keys) && isset($keys[0])) {
                $pk = $keys[0];
            }
        }
            
        // Data is valid, let's store it!
        if ($dbOperations) {
            $action = $this->_queryType;
            if ($this->_queryType == DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT) {
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

            // process tripleLink stuff
            if ($pk && !empty($this->_do->$pk)) { // has only sense if we have a valid primary key
                if (isset($this->tripleLinks) && is_array($this->tripleLinks)) {
                    foreach ($this->tripleLinks as $tripleLink) {
                        $do = DB_DataObject::factory($tripleLink['table']);

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


                        $do->$fromField = $this->_do->$pk;
                        $do->delete();
            
                        $rows = $values['__tripleLink_' . $tripleLink['table']];
                        if (count($rows) > 0) {
                            foreach ($rows as $rowid=>$row) {
                                if (count($row) > 0) {
                                    foreach ($row as $fieldvalue) {
                                        $do = DB_DataObject::factory($tripleLink['table']);
                                        $do->$fromField = $this->_do->$pk;
                                        $do->$toField1 = $rowid;
                                        $do->$toField2 = $fieldvalue;
                                        $do->insert();
                                    }
                                }
                            }
                        }
                    }
                }
            
                if (isset($this->crossLinks) && is_array($this->crossLinks)) {
                    foreach ($this->crossLinks as $crossLink) {
                        $do = DB_DataObject::factory($crossLink['table']);
                        $links = $do->links();

                        //after $links = $do->links(); in the crossLinks code
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

                        $do->$fromField = $this->_do->$pk;
                        $do->delete();
                        $fieldvalues = $values['__crossLink_' . $crossLink['table']];
                        if (count($fieldvalues) > 0) {
                            foreach ($fieldvalues as $fieldvalue) {
                                $do = DB_DataObject::factory($crossLink['table']);
                                $do->$fromField = $this->_do->$pk;
                                $do->$toField = $fieldvalue;
                                $do->insert();
                            }
                        }
                    }
                }
            }
        }

        if (method_exists($this->_do, 'postprocessform')) {
            $this->_do->postProcessForm($values);
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
    function forceQueryType($queryType = DB_DATAOBJECT_FORMBUILDER_QUERY_AUTODETECT)
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
     * If the "fb_fieldsToRender" property in a DataObject is not set, all fields
     * will be rendered as form fields.
     * When the property is set, a field will be rendered only if:
     * 1. it is a primary key
     * 2. it's explicitly requested in $do->fb_fieldsToRender
     *
     * @access private
     * @return array   The fields that shall be rendered
     */
    function _getFieldsToRender()
    {
        $all_fields = array_merge($this->_do->table(), $this->_getCrossLinkElementNames());
        if (isset($this->fieldsToRender) && is_array($this->fieldsToRender)) {
            // a little workaround to get an array like [FIELD_NAME] => FIELD_TYPE (for use in _generateForm)
            // maybe there's some better way to do this:
            $result = array();

            $key_fields = $this->_do->keys();
            if (!is_array($key_fields)) {
                $key_fields = array();
            }
            $fields_to_render = $this->fieldsToRender;

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
     * make some fields uneditable, you have to set the "fb_userEditableFields" property
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
        // if you don't want any of your fields to be editable by the user, set fb_userEditableFields to
        // "array()" in your DataObject-derived class
        if (isset($this->userEditableFields) && is_array($this->userEditableFields)) {
            return $this->userEditableFields;
        }
        // all fields may be updated by the user since fb_userEditableFields is not set
        if (isset($this->fieldsToRender) && is_array($this->fieldsToRender)) {
            return $this->fieldsToRender;
        }
        return array_keys($this->_getFieldsToRender());
    }
    
}

?>

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
// | Author:  Justin Patrin <justinpatrin@php.net>                        |
// +----------------------------------------------------------------------+

/**
 * @package  DB_DataObject_FormBuilder
 * @author   Justin Patrin <justinpatrin@php.net>
 * @version  $Id$
 */

require_once('HTML/QuickForm/element.php');

/**
 * An HTML_QuickForm element which creates a table of elements
 */
class DB_DataObject_FormBuilder_QuickForm_ElementTable extends HTML_QuickForm_element {

    /**
     * Array of arrays of HTML_QuickForm elements
     *
     * @var array
     */
    var $_rows = array();

    /**
     * Array of column names (strings)
     *
     * @var array
     */
    var $_columnNames = array();

    /**
     * Array of row names (strings)
     *
     * @var array
     */
    var $_rowNames = array();

    /**
     * Constructor
     *
     * @param string name for the element
     * @param string label for the element
     */
    function DB_DataObject_FormBuilder_QuickForm_ElementTable($name = null, $label = null/*, $columnNames = null,
                                                               $rowNames = null, $rows = null, $attributes = null*/) {
        parent::HTML_QuickForm_element($name, $label);
        //$this->setRows($rows);
        //$this->setColumnNames($columnNames);
        //$this->setRowNames($rowNames);
    }

    /**
     * Sets the column names
     *
     * @param array array of column names (strings)
     */
    function setColumnNames($columnNames) {
        $this->_columnNames = $columnNames;
    }

    /**
     * Adds a column name
     *
     * @param string name of the column
     */
    function addColumnName($columnName) {
        $this->_columnNames[] = $columnName;
    }
    
    /**
     * Set the row names
     *
     * @param array array of row names (strings)
     */
    function setRowNames($rowNames) {
        $this->_rowNames = $rowNames;
    }

    /**
     * Sets the rows
     *
     * @param array array of HTML_QuickForm elements
     */
    function setRows(&$rows) {
        $this->_rows =& $rows;
    }

    /**
     * Adds a row to the table
     *
     * @param array array of HTML_QuickForm elements
     * @param string name of the row
     */
    function addRow(&$row, $rowName = null) {
        $this->_rows[] =& $row;
        if ($rowName !== null) {
            $this->addRowName($rowName);
        }
    }

    /**
     * Adds a row name
     *
     * @param string name of the row
     */
    function addRowName($rowName) {
        $this->_rowNames[] = $rowName;
    }

    /**
     * Freezes all checkboxes in the table
     */
    function freeze() {
        parent::freeze();
        foreach (array_keys($this->_rows) as $key) {
            foreach (array_keys($this->_rows[$key]) as $key2) {
                $this->_rows[$key][$key2]->freeze();
            }
        }
    }

    /**
     * Returns Html for the group
     * 
     * @access      public
     * @return      string
     */
    function toHtml()
    {
        include_once ('HTML/Table.php');
        $tripleLinkTable = new HTML_Table();
        $tripleLinkTable->setAutoGrow(true);
        $tripleLinkTable->setAutoFill('');
        $row = 0;
        $col = 0;

        foreach ($this->_columnNames as $key => $value) {
            ++$col;
            $tripleLinkTable->setCellContents($row, $col, $value);
            $tripleLinkTable->setCellAttributes($row, $col, array('style' => 'text-align: center'));
        }

        foreach (array_keys($this->_rows) as $key) {
            ++$row;
            $col = 0;
            $tripleLinkTable->setCellContents($row, $col, $this->_rowNames[$key]);
            foreach (array_keys($this->_rows[$key]) as $key2) {
                ++$col;
                $tripleLinkTable->setCellContents($row, $col, $this->_rows[$key][$key2]->toHTML());
                $tripleLinkTable->setCellAttributes($row, $col, array('style' => 'text-align: center'));
            }
        }
        $hrAttrs = array('bgcolor' => 'lightgrey');
        $tripleLinkTable->setRowAttributes(0, $hrAttrs, true);
        $tripleLinkTable->setColAttributes(0, $hrAttrs);
        return $tripleLinkTable->toHTML();

        /*include_once('HTML/QuickForm/Renderer/Default.php');
        $renderer =& new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        $this->accept($renderer);
        return $renderer->toHtml();*/
    } //end func toHtml

    /**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string  Name of event
     * @param     mixed   event arguments
     * @param     object  calling object
     * @access    public
     * @return    bool    true
     */
    function onQuickFormEvent($event, $arg, &$caller)
    {
        switch ($event) {
            case 'updateValue':
                foreach (array_keys($this->_rows) as $key) {
                    foreach (array_keys($this->_rows[$key]) as $key2) {
                        $this->_rows[$key][$key2]->onQuickFormEvent('updateValue', null, $caller);
                    }
                }
                break;

            default:
                parent::onQuickFormEvent($event, $arg, $caller);
        }
        return true;
    }
}

?>
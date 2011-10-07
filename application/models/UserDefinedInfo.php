<?php
/**
 * Class providing access to user defined fields
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package Models
 * @filesource
 */
/**
 Includes
 */
require_once 'Braintacle/MDB2.php';
/**
 * User defined fields for a computer
 *
 * The 'tag' field is always present. Other fields may be defined by the
 * administrator. Their names are always returned lowercase.
 * @package Models
 */
class Model_UserDefinedInfo extends Model_Abstract
{

    /**
     * Datatypes of all properties.
     *
     * Initially empty, typically managed by {@link getTypes()}.
     * Do not use it directly - always call getTypes() or getPropertyTypes().
     * @var array
     */
    static protected $_allTypesStatic = array();

    /**
     * Computer this instance is linked to
     *
     * This is set if a computer was passed to the constructor
     * @var Model_Computer
     */
    protected $_computer;

    /**
     * Constructor
     *
     * If a {@link Model_Computer} object is passed, it will be linked to this
     * instance and the data for this computer will be available as properties
     * and for setting via {@link setValues()}.
     * @param Model_Computer $computer
     */
    function __construct(Model_Computer $computer=null)
    {
        parent::__construct();

        // Construct array of datatypes
        $this->_types = self::getTypes();

        // Construct property map. Key and value are identical.
        foreach ($this->_types as $name => $type) {
            $this->_propertyMap[$name] = $name;
        }

        // Load values if a computer ID is given
        if (!is_null($computer)) {
            $db = Zend_Registry::get('db');

            $data = $db->fetchRow(
                'SELECT * FROM accountinfo WHERE hardware_id = ?',
                $computer->getId()
            );
            foreach ($data as $property => $value) {
                if ($property != 'hardware_id') { // Not a property, ignore
                    $this->setProperty($property, $value);
                }
            }

            // Keep track of computer for later updates
            $this->_computer = $computer;
        }
    }

    /**
     * Set the values of user defined fields and store them in the database
     *
     * This method only works if a computer was passed to the constructor.
     * Values not specified in $values will remain unchanged.
     * @param array $values Associative array with the values.
     */
    public function setValues($values)
    {
        if (!$this->_computer) {
            throw new RuntimeException('No Computer was associated with this object');
        }

        // Have input processed by setProperty() to ensure valid data and to
        // update the object's internal state
        foreach ($values as $property => $value) {
            $this->setProperty($property, $value);
        }

        $db = Zend_Registry::get('db');

        $db->update(
            'accountinfo',
            $values,
            $db->quoteInto('hardware_id = ?', $this->_computer->getId())
        );
    }

    /**
     * Return the datatypes of all user defined fields
     *
     * Reimplementation that just proxies {@link getTypes()}.
     * @return array Associative array with the datatypes.
     */
    public function getPropertyTypes()
    {
        return Model_UserDefinedInfo::getTypes();
    }

    /**
     * Static variant of {@link getPropertyTypes()}
     *
     * This method makes an extra database connection via MDB2. The result is
     * stored statically, so that no extra connections are made when this
     * gets called more than once.
     * @return array Associative array with the datatypes
     */
    static function getTypes()
    {
        if (empty(self::$_allTypesStatic)) { // Query database only once
            Braintacle_MDB2::setErrorReporting();
            $mdb2 = Braintacle_MDB2::factory();
            $columns = $mdb2->reverse->tableInfo('accountinfo');
            Braintacle_MDB2::resetErrorReporting();

            foreach ($columns as $column) {
                $name = $column['name'];
                if ($name != 'hardware_id') {
                    self::$_allTypesStatic[$name] = $column['mdb2type'];
                }
            }
        }
        return self::$_allTypesStatic;
    }

}

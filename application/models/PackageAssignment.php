<?php
/**
 * Class representing the association of a package to a computer
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 */
/**
 * Package association
 *
 * Properties:
 *
 * - <b>Name</b> Package name
 * - <b>Status</b> Status on this computer
 * @package Models
 */
class Model_PackageAssignment extends Model_ChildObject
{
    /**
     * Database-internal date format
     *
     * This format can be passed to \date() and related functions to create a
     * date string that is used to store the package assignment date in the
     * database. It is similar to the format created by the server except that
     * the day is zero-padded instead of space-padded. Code that parses these
     * date strings should be prepared to handle both variants.
     *
     * The date is not timezone-aware and should be assumed to be local time.
     */
    const DATEFORMAT = 'D M d H:i:s Y';

    /**
     * Database value for status "not notified"
     */
    const NOT_NOTIFIED = null;

    /**
     * Database value for status "notified"
     */
    const NOTIFIED = 'NOTIFIED';

    /**
     * Database value for status "success"
     */
    const SUCCESS = 'SUCCESS';

    /**
     * Prefix of database value for error status
     */
    const ERROR_PREFIX = 'ERR';

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from query result
        'Computer' => 'hardware_id',
        'Name' => 'name',
        'Status' => 'tvalue',
        'Timestamp' => 'comments'
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Timestamp' => 'timestamp',
    );

    /**
     * Return a statement|select object with all objects matching criteria.
     *
     * This implementation ignores $columns and always returns all properties.
     */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        $db = Model_Database::getAdapter();

        if (is_null($order)) {
            $order = 'Name';
        }
        $order = self::getOrder($order, $direction, $this->_propertyMap);

        $select = $db->select()
            ->from('devices', array('hardware_id', 'tvalue', 'comments'))
            ->join(
                'download_enable',
                'devices.ivalue=download_enable.id',
                array()
            )
            ->join(
                'download_available',
                'download_enable.fileid=download_available.fileid',
                array('name')
            )
            ->where("devices.name='DOWNLOAD'")
            ->order($order);

        if (!is_null($filters) and isset($filters['Computer'])) {
            $select->where('hardware_id = ?', (int) $filters['Computer']);
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

    /**
     * Return the name of the table which stores this object.
     *
     * This class does not operate on a single table and therefore throws an
     * exception.
     */
    public function getTableName()
    {
        throw (
            new ErrorException(
                'getTableName() can not be called on Model_PackageAssignment'
            )
        );
    }

    /**
     * Retrieve a property by its logical name
     *
     * Converts the timestamp from the internal format to ISO.
     */
    public function getProperty($property, $rawValue=false)
    {
        if ($rawValue or $property != 'Timestamp') {
            return parent::getProperty($property, $rawValue);
        }

        $value = parent::getProperty('Timestamp', true);
        if (empty($value)) {
            return null;
        }

        $value = \DateTime::createFromFormat(self::DATEFORMAT, $value);
        return new \Zend_Date($value->getTimestamp(), \Zend_Date::TIMESTAMP);
    }

}

<?php
/**
 * Class representing a group of computers
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
 * A group of computers
 *
 * Packages and settings assigned to a group will be assigned to all computers
 * which are a member of the group. There are 2 types of group membership:
 * dynamic membership is based on the result of an SQL query, while static
 * membership is assigned manually. Both types can be present within the same
 * group. It is also possible to exclude a computer from a group when it would
 * match the condition for dynamic membership.
 *
 * Properties:
 * - <b>Id:</b> primary key
 * - <b>Name:</b> display name
 * - <b>Description:</b> description
 * - <b>CreationDate:</b> timestamp of group creation
 * - <b>DynamicMembersSql:</b> SQL query for dynamic members, may be empty
 * - <b>DynamicMembersXml:</b> XML definition for dynamic members (not supported yet), may be empty
 * - <b>CacheCreationDate:</b> Timestamp of last cache computation
 * - <b>CacheExpirationDate:</b> Timestamp when cache will expire and be rebuilt
 *
 *
 * To set CacheCreationDate and CacheExpirationDate manually, {@link setProperty()}
 * should always be used.
 *
 * The representation of groups in the database is a bit odd. Each group has a
 * row in the 'hardware' table with the 'deviceid' column set to
 * '_SYSTEMGROUP_'. Only the 'id', 'deviceid', 'name', 'lastdate' and
 * 'description' columns are used. For each group there is a corresponding row
 * in the 'groups' table. It is only relevant for dynamic membership. All fields
 * except hardware_id may be empty/NULL/0.
 *
 * - hardware_id: matches hardware.id.
 * - request: SQL query delivering 1 column with hardware IDs of group members.
 * - xmldef: alternate query definition. Not supported yet.
 * - create_time: UNIX timestamp of last cache computation, see below.
 * - revalidate_from: create_time + random offset, see below
 *
 *
 * The 'groups_cache' table contains membership information:
 *
 * - hardware_id: PK of computer
 * - group_id: PK of group
 * - static: 0 for cached dynamic membership, 1 for statically included, 2 for excluded
 *
 *
 * Dynamic membership is determined exclusively from the cache, so the
 * information might be out of date. Any method that accesses the groups_cache
 * table directly should call {@link update()} before doing that.
 *
 * Two config variables control how often the cache gets rebuilt.
 * <i>GroupCacheExpirationInterval</i> is the minimum number of seconds between
 * rebuilds for a particular group. To prevent recomputation of all groups at
 * once (which may be a resource-intensive process), a random number of seconds
 * between 0 and <i>GroupCacheExpirationFuzz</i> is added.
 * @package Models
 */
class Model_Group extends Model_ComputerOrGroup
{

    /**
     * Property Map
     * @var array
     */
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'Id' => 'id',
        'Name' => 'name',
        'CreationDate' => 'lastdate',
        'Description' => 'description',
        // Values from 'groups' table
        'DynamicMembersSql' => 'request',
        'DynamicMembersXml' => 'xmldef',
        'CacheExpirationDate' => 'revalidate_from', // Will be mangled by {@link getProperty()}
        'CacheCreationDate' => 'create_time',
    );

    /**
     * Non-text datatypes
     * @var array
     */
    protected $_types = array(
        'Id' => 'integer',
        'CreationDate' => 'timestamp',
        'CacheExpirationDate' => 'timestamp',
        'CacheCreationDate' => 'timestamp',
    );


    /**
     * Return a statement object with all groups
     * @param array $columns Properties which should be returned. Default: all properties
     * @param string $filter Optional filter to apply (Id|Expired), default: return all groups
     * @param mixed $filterArg Value to filter by
     * @param string $order Logical property to sort by. Default: null
     * @param string $direction one of [asc|desc]. Default: asc
     * @return Zend_Db_Statement Query result
     */
    static function createStatementStatic(
        $columns=null,
        $filter = null,
        $filterArg = null,
        $order=null,
        $direction='asc'
    )
    {
        $db = Zend_Registry::get('db');

        $dummy = new Model_Group;
        $map = $dummy->getPropertyMap();

        if (empty($columns)) {
            $columns = array_keys($map); // Select all properties
        }

        $fromGroups = array();
        foreach ($columns as $column) {
            switch ($column) {
                case 'DynamicMembersSql':
                case 'DynamicMembersXml':
                case 'CacheExpirationDate':
                case 'CacheCreationDate':
                    $fromGroups[] = $map[$column];
                    break;
                default:
                    if (isset($map[$column])) { // ignore nonexistent columns
                        $fromHardware[] = $map[$column];
                    }
                    break;
            }
        }

        // add PK if not already selected
        if (!in_array('id', $fromHardware)) {
            $fromHardware[] = 'id';
        }

        $select = $db->select()
            ->from('hardware', $fromHardware)
            ->order(self::getOrder($order, $direction, $map));

        switch ($filter) {
            case '':
                break;
            case 'Id':
                $select->where('id=?', (integer) $filterArg);
                break;
            case 'Expired':
                $column = $map['CacheExpirationDate'];
                if (!in_array($column, $fromGroups)) {
                    $fromGroups[] = $column;
                }

                $now = Zend_Date::now()->get(Zend_Date::TIMESTAMP);
                $select->where(
                    'revalidate_from <= ?',
                    $now - Model_Config::getOption('GroupCacheExpirationInterval')
                );
                break;
            default:
                throw new UnexpectedValueException(
                    'Invalid group filter: ' . $filter
                );
                break;
        }

        if (!empty($fromGroups)) {
            $select->join(
                'groups',
                'hardware.id = groups.hardware_id',
                $fromGroups
            );
        } else {
            // Only return groups, not computers. Not necessary if the 'groups'
            // table has been joined since the join condition only matches
            // groups.
            $select->where("deviceid = '_SYSTEMGROUP_'");
        }

        return $select->query();
    }

    /**
     * Retrieve a property by its logical name
     *
     * CacheExpirationDate and CacheCreationDate are automatically converted to
     * a Zend_Date object unless $rawValue is true. Additionally, the value of
     * the global GroupCacheExpirationInterval Option is added to
     * CacheExpirationDate, so that the real expiration date is returned instead
     * of the value in the database (which is CacheCreationDate + random offset)
     */
    public function getProperty($property, $rawValue=false)
    {
        if (!$rawValue and ($property == 'CacheExpirationDate' or $property == 'CacheCreationDate')) {
            $value = new Zend_Date(
                parent::getProperty($property, true),
                Zend_Date::TIMESTAMP
            );
            if ($property == 'CacheExpirationDate') {
                $value->addSecond(Model_Config::getOption('GroupCacheExpirationInterval'));
            }
        } else {
            $value = parent::getProperty($property, $rawValue);
        }

        return $value;
    }

    /**
     * Set a property by its logical name.
     *
     * For CacheCreationDate and CacheExpirationDate, a Zend_Date object is
     * expected, which will be processed to match the internal representation.
     * This is strongly recommended over calling setCacheCreationDate() and
     * setCacheExpirationDate(), which would require knowledge of the inner
     * logic.
     */
    public function setProperty($property, $value)
    {
        $columnName = $this->getColumnName($property);

        if ($property == 'CacheExpirationDate') {
            // Create new object to leave original object untouched
            $value = new Zend_Date($value);
            $value->subSecond(Model_Config::getOption('GroupCacheExpirationInterval'));
            $this->__set($columnName, $value->get(Zend_Date::TIMESTAMP));
        } elseif ($property == 'CacheCreationDate') {
            $this->__set($columnName, $value->get(Zend_Date::TIMESTAMP));
        } else {
            parent::setProperty($property, $value);
        }
    }

    /**
     * Get a Model_Group object for the given primary key.
     * @param int $id Primary key
     * @return mixed Fully populated Model_Group object, FALSE if no group was found
     */
    static function fetchById($id)
    {
        return self::createStatementStatic(null, 'Id', $id)->fetchObject('Model_Group');
    }

    /**
     * Return a statement object with names of all packages associated with this group
     * @param string $direction one of [asc|desc]. Default: asc
     * @return Zend_Db_Statement Query result
     */
    public function getPackages($direction='asc')
    {
        $db = Zend_Registry::get('db');

        $select = $db->select()
            ->from('devices', array())
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
            ->where('hardware_id = ?', (int) $this->getId())
            ->where("devices.name='DOWNLOAD'")
            ->order(self::getOrder('Name', $direction, $this->_propertyMap));

        return $select->query();
    }

    /**
     * Update the cache for dynamic members
     *
     * Dynamic members are always determined from the cache. This method updates
     * the cache for this particular group. By default, the cache is not updated
     * before its expiration time has been reached. This method will do nothing
     * in that case. Set $force to TRUE to rebuild the cache in any case.
     * @param bool $force Always rebuild cache, regardless of expiration time.
     */
    public function update($force = false)
    {
        $criteria = $this->getDynamicMembersSql();
        if (!$criteria) {
            return; // Nothing to do if no SQL query defined for this group
        }

        $expires = $this->getCacheExpirationDate();
        $currentTime = Zend_Date::now();

        // Do nothing if expiration time has not been reached and $force is false.
        if (!$force and ($expires->compare($currentTime) == 1)) {
            return;
        }

        if ($this->getDynamicMembersXml()) {
            throw new RuntimeException('XML group definition not supported yet');
        }

        if (!$this->lock()) {
            return; // Another process is currently updating this group.
        }

        $db = Zend_Registry::get('db');

        // Delete computers from the cache which no longer meet the criteria
        $db->delete(
            'groups_cache',
            array(
                'group_id = ?' => $this->getId(),
                'static = ?' => Model_GroupMembership::TYPE_DYNAMIC,
                "hardware_id NOT IN ($criteria)" => null
            )
        );

        // Insert computers which meet the criteria and don't already have an
        // entry in the cache (which might be dynamic, static or excluded).
        // Also filter group IDs from criteria, i.e. only computers will show up
        // in the cache.
        $newIds = $db
            ->select()
            ->from('hardware', array('id'))
            ->where("id IN ($criteria)")
            ->where(
                'id NOT IN (SELECT hardware_id FROM groups_cache WHERE group_id=?)',
                $this->getId()
            )
            ->where('id NOT IN (SELECT hardware_id FROM groups)')
            ->query();
        while ($computer = $newIds->fetchColumn()) {
            $db->insert(
                'groups_cache',
                array(
                    'group_id' => $this->getId(),
                    'hardware_id' => $computer,
                    'static' => Model_GroupMembership::TYPE_DYNAMIC
                )
            );
        }

        // Update CacheCreationDate and CacheExpirationDate in the database
        $fuzz = mt_rand(0, Model_Config::getOption('GroupCacheExpirationFuzz'));
        $minExpires = new Zend_Date($currentTime);
        $minExpires->addSecond($fuzz);

        $db->update(
            'groups',
            array(
                'create_time' => $currentTime->get(Zend_Date::TIMESTAMP),
                'revalidate_from' => $minExpires->get(Zend_Date::TIMESTAMP)
            ),
            array('hardware_id = ?' => $this->getId())
        );

        $this->unlock();

        // Update CacheCreationDate and CacheExpirationDate properties
        $this->setProperty('CacheCreationDate', $currentTime);
        // Do not use setProperty() here to avoid unnecessary calculations.
        $this->__set('revalidate_from', $minExpires->get(Zend_Date::TIMESTAMP));
    }

    /**
     * Update the cache for dynamic members for all groups
     *
     * Dynamic members are always determined from the cache. This method updates
     * the cache for all groups. By default, the cache is only updated for
     * groups whose expiration time has been reached. Set $force to TRUE to
     * rebuild the cache for all groups in any case.
     * @param bool $force Always rebuild cache, regardless of expiration time.
     */
    static function updateAll($force = false)
    {
        if ($force) {
            $filter = null;
        } else {
            $filter = 'Expired';
        }

        $groups = self::createStatementStatic(
            null,
            $filter
        );

        while ($group = $groups->fetchObject('Model_Group')) {
            $group->update(true);
        }
    }

    /**
     * Delete this group from the database
     * @param bool $reuseLock If this instance already has a lock, reuse it.
     * @return bool Success
     */
    public function delete($reuseLock=false)
    {
        // A lock is required
        if ((!$reuseLock or !$this->isLocked()) and !$this->lock()) {
            return false;
        }

        $db = Zend_Registry::get('db');
        $id = $this->getId();

        // Start transaction to keep database consistent in case of errors
        // If a transaction is already in progress, an exception will be thrown
        // by PDO which has to be caught. The commit() and rollBack() methods
        // can only be called if the transaction has been started here.
        try{
            $db->beginTransaction();
            $transaction = true;
        } catch (Exception $exception) {
            $transaction = false;
        }

        try {
            // Delete rows
            $db->delete('groups_cache', array('group_id = ?' => $id));
            $db->delete('devices', array('hardware_id = ?' => $id));
            $db->delete('groups', array('hardware_id = ?' => $id));
            $db->delete('hardware', array('id = ?' => $id));
        } catch (Exception $exception) {
            if ($transaction) {
                $db->rollBack();
            }
            throw $exception;
        }

        if ($transaction) {
            $db->commit();
        }

        $this->unlock();
        return true;
    }

}

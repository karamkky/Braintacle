<?php
/**
 * Tests for the NetworkDevicesScanned table
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
 */

namespace Database\Test\Table;

class NetworkDevicesScannedTest extends AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
    }

    public function testHydrator()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf('Zend\Stdlib\Hydrator\ArraySerializable', $hydrator);

        $map = $hydrator->getNamingStrategy();
        $this->assertInstanceOf('Database\Hydrator\NamingStrategy\MapNamingStrategy', $map);

        $this->assertEquals('IpAddress', $map->hydrate('ip'));
        $this->assertEquals('MacAddress', $map->hydrate('mac'));
        $this->assertEquals('Hostname', $map->hydrate('name'));
        $this->assertEquals('DiscoveryDate', $map->hydrate('date'));
        $this->assertEquals('Description', $map->hydrate('description'));
        $this->assertEquals('Type', $map->hydrate('type'));

        $this->assertEquals('ip', $map->extract('IpAddress'));
        $this->assertEquals('mac', $map->extract('MacAddress'));
        $this->assertEquals('name', $map->extract('Hostname'));
        $this->assertEquals('date', $map->extract('DiscoveryDate'));
        $this->assertEquals('description', $map->extract('Description'));
        $this->assertEquals('type', $map->extract('Type'));

        $this->assertInstanceOf(
            'Zend\Stdlib\Hydrator\Strategy\DateTimeFormatterStrategy', $hydrator->getStrategy('DiscoveryDate')
        );
        $this->assertInstanceOf(
            'Zend\Stdlib\Hydrator\Strategy\DateTimeFormatterStrategy', $hydrator->getStrategy('date')
        );
        $this->assertInstanceOf('Library\Hydrator\Strategy\MacAddress', $hydrator->getStrategy('MacAddress'));
        $this->assertInstanceOf('Library\Hydrator\Strategy\MacAddress', $hydrator->getStrategy('mac'));

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Zend\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertEquals($hydrator, $resultSet->getHydrator());
    }
}
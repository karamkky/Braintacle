<?php
/**
 * Factory for Model\Package\PackageManager
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

namespace Model\Service\Package;

/**
 * Factory for Model\Package\PackageManager
 */
class PackageManagerFactory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function createService(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        return new \Model\Package\PackageManager(
            $serviceLocator->get('Model\Package\Storage\Direct'),
            $serviceLocator->get('Model\Config'),
            $serviceLocator->get('Library\ArchiveManager'),
            $serviceLocator->get('Database\Table\Packages'),
            $serviceLocator->get('Database\Table\ClientConfig'),
            $serviceLocator->get('Database\Table\GroupInfo')
        );
    }
}

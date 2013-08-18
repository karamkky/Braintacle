<?php
/**
 * Configuration for Console module
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Controller;

use Zend\Stdlib;

/**
 * Transitional controller that starts the ZF1 application
 */
class ZF1Controller implements Stdlib\DispatchableInterface
{
    /**
     * Run ZF1 application and exit
     */
    public function dispatch(Stdlib\RequestInterface $request, Stdlib\ResponseInterface $response = null)
    {
        \Library\Application::$application->run();
        exit;
    }
}
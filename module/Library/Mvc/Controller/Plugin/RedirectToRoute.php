<?php
/**
 * Redirect to standard route (controller/action)
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Mvc\Controller\Plugin;

/**
 * Redirect to standard route (controller/action)
 *
 * This is a convenient alternative to ZF's Redirect plugin which composes the
 * redirect URL via the UrlFromRoute plugin.
 */
class RedirectToRoute extends \Zend\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Redirect to given route
     *
     * All arguments get urlencode()d before being used. Calling code must not
     * encode any arguments.
     *
     * @param string $controllerName Controller name. If empty, the default controller is used.
     * @param string $action Action name. If empty, the default action is used.
     * @param array $params Associative array of URL parameters
     * @return \Zend\Http\Response Redirect response
     */
    public function __invoke($controllerName = null, $action = null, array $params = array())
    {
        $controller = $this->getController();
        return $controller->redirect()->toUrl(
            $controller->urlFromRoute($controllerName, $action, $params)
        );
    }
}

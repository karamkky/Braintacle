<?php
/**
 * Base class for controller plugin tests
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

namespace Library\Test\Mvc\Controller\Plugin;

use \Library\Application;

/**
 * Base class for controller plugin tests
 *
 * Tests for controller plugin classes can derive from this class for some
 * convenience functions. Additionally, the testPluginInterface() test is
 * executed for all derived tests. 
*/
abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Controller used for tests, if set by _getPlugin()
     * @var \Zend\Stdlib\DispatchableInterface
     */
    protected $_controller;

    /**
     * Get the name of the controller plugin, derived from the test class name
     *
     * @return string Plugin name
     */
    protected function _getPluginName()
    {
        // Derive plugin name from test class name (minus namespace and 'Test' suffix)
        return substr(strrchr(get_class($this), '\\'), 1, -4);
    }

    /**
     * Get the application's configured controller plugin manager
     *
     * @return \Zend\Mvc\Controller\PluginManager
     */
    protected function _getPluginManager()
    {
        return Application::getService('ControllerPluginManager');
    }

    /**
     * Get an initialized instance of the controller plugin
     *
     * If controller setup is requested, the controller will be a
     * \Library\Test\Mvc\Controller\TestController. Its MvcEvent will be
     * initialized with a standard route 'test' (/module/controller/action/)
     * with defaults of "defaultcontroller" and "defaultaction".
     * The RouteMatch is initialized with "currentcontroller" and
     * "currentaction". An empty response is created.
     *
     * @param bool $setController Initialize the helper with a working controller (default: TRUE)
     * @return \Zend\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function _getPlugin($setController=true)
    {
        if ($setController) {
            $router = new \Zend\Mvc\Router\Http\TreeRouteStack;
            $router->addRoute(
                'test',
                \Zend\Mvc\Router\Http\Segment::factory(
                    array(
                        // Match "module" prefix, followed by controller and action
                        // names. All three components are optional except the
                        // controller, which is required if an action is given.
                        // Matches with or without trailing slash.
                        'route' => '/[module[/]][:controller[/][:action[/]]]',
                        'defaults' => array(
                            'controller' => 'defaultcontroller',
                            'action' => 'defaultaction',
                        ),
                    )
                )
            );

            $routeMatch = new \Zend\Mvc\Router\RouteMatch(
                array(
                    'controller' => 'currentcontroller',
                    'action' => 'currentaction',
                )
            );
            $routeMatch->setMatchedRouteName('test');

            $event = new \Zend\Mvc\MvcEvent;
            $event->setRouter($router);
            $event->setRouteMatch($routeMatch);
            $event->setResponse(new \Zend\Http\Response);

            // Register TestController with the service manager because this is
            // not done in the module setup
            $manager = Application::getService('ControllerLoader');
            $manager->setInvokableClass('test', 'Library\Test\Mvc\Controller\TestController');
            $this->_controller = $manager->get('test');
            $this->_controller->setEvent($event);

            return $this->_controller->plugin($this->_getPluginName());
        } else {
            return $this->_getPluginManager()->get($this->_getPluginName());
        }
    }

    /**
     * Test if the plugin is properly registered with the service manager
     */
    public function testPluginInterface()
    {
        // Test if the plugin is registered with the application's service manager
        $this->assertTrue($this->_getPluginManager()->has($this->_getPluginName()));

        // Get plugin instance through service manager and test for required interface
        $this->assertInstanceOf('Zend\Mvc\Controller\Plugin\PluginInterface', $this->_getPlugin(false));
    }
}

<?php
/**
 * Abstract form test case
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test;

/**
 * Abstract form test case
 *
 * This base class performs common setup and tests for all forms derived from
 * \Console\Form\AbstractForm.
 */
abstract class AbstractFormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Form instance provided by setUp()
     * @var \Console\Form\AbstractForm
     */
    protected $_form;

    /**
     * Set up form instance
     */
    public function setUp()
    {
        $this->_form = $this->_getForm();
    }

    /**
     * Hook to provide form instance
     *
     * The default implementation pulls an instance from the form element
     * manager using a name derived from the test class name. Override this
     * method to use another name or construct the form instance manually. In
     * the latter case, the overridden method is responsible for calling
     * init() on the form.
     */
    protected function _getForm()
    {
        return \Library\Application::getService('FormElementManager')->get($this->_getFormClass());
    }

    /**
     * Get the name of the form class, derived from the test class name
     *
     * @return string Form class name
     */
    protected function _getFormClass()
    {
        // Derive form class from test class name (minus \Test namespace and 'Test' suffix)
        return substr(str_replace('\Test', '', get_class($this)), 0, -4);
    }

    /**
     * Get a view renderer
     *
     * @return \Zend\View\Renderer\PhpRenderer
     */
    protected function _getView()
    {
        return \Library\Application::getService('ViewManager')->getRenderer();
    }

    /**
     * Test basic form properties (form class, "class" attribute, CSRF element)
     */
    public function testForm()
    {
        $this->assertInstanceOf('Console\Form\AbstractForm', $this->_form);
        $this->assertEquals(
            'form ' . substr(strtr(strtolower($this->_getFormClass()), '\\', '_'), 8),
            $this->_form->getAttribute('class')
        );
        $this->assertInstanceOf('\Zend\Form\Element\Csrf', $this->_form->get('_csrf'));
    }
}

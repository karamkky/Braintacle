<?php
/**
 * Tests for Login form
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\Form;

/**
 * Tests for Login form
 */
class LoginTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('User'));
        $this->assertInstanceOf('Zend\Form\Element\Password', $this->_form->get('Password'));
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testRender()
    {
        $view = $this->_createView();
        $html = $this->_form->render($view);
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, \Zend\Dom\Document\Query::execute('//form', $document));
        $this->assertContains(
            'document.forms["form_login"]["User"].focus()',
            $view->placeholder('BodyOnLoad')
        );
    }

    public function testInputFilter()
    {
        $data = array(
            'User' => 'user',
            'Password' => 'password',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('user', $data['User']);
        $this->assertEquals('password', $data['Password']);
    }

    public function testInputFilterEmptyValues()
    {
        $data = array(
            'User' => '',
            'Password' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('', $data['User']);
        $this->assertEquals('', $data['Password']);
    }

    public function testInputFilterWhitespaceValues()
    {
        $data = array(
            'User' => ' ',
            'Password' => ' ',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals(' ', $data['User']);
        $this->assertEquals(' ', $data['Password']);
    }
}

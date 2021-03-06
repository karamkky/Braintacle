<?php
/**
 * Tests for the ManageRegistryValues Helper
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\View\Helper\Form;

class SoftwareTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function _getHelperName()
    {
        return 'consoleFormSoftware';
    }

    public function testInvoke()
    {
        $csrf = $this->createMock('Zend\Form\Element\Csrf');
        $softwareFieldset = $this->createMock('Zend\Form\Fieldset');

        $form = $this->createMock('Console\Form\Software');
        $form->expects($this->at(0))->method('prepare');
        $form->expects($this->at(1))->method('get')->with('_csrf')->willReturn($csrf);
        $form->expects($this->at(2))->method('get')->with('Software')->willReturn($softwareFieldset);

        $consoleForm = $this->createMock('Console\View\Helper\Form\Form');
        $consoleForm->method('postMaxSizeExceeded')->willReturn('EXCEEDED');
        $consoleForm->method('openTag')->with($form)->willReturn('<form>');
        $consoleForm->method('closeTag')->willReturn('</form>');

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->method('__invoke')->with($csrf)->willReturn('<csrf>');

        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $view->method('plugin')->willReturnMap([
            ['consoleForm', null, $consoleForm],
            ['formRow', null, $formRow],
        ]);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['__invoke'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);
        $helper->method('renderButtons')->with($form, 'filter')->willReturn('<buttons>');
        $helper->method('renderSoftwareFieldset')
               ->with($softwareFieldset, 'software', 'sorting')
               ->willReturn('<software>');

        $this->assertEquals(
            'EXCEEDED<form><csrf><buttons><software></form>',
            $helper($form, 'software', 'sorting', 'filter')
        );
    }

    public function renderButtonsProvider()
    {
        return [
            ['accepted', "\nIGNORE"],
            ['ignored', "ACCEPT\n"],
            ['', "ACCEPT\nIGNORE"],
        ];
    }

    /** @dataProvider renderButtonsProvider */
    public function testRenderButtons($filter, $buttons)
    {
        $accept = $this->createMock('Zend\Form\ElementInterface');
        $ignore = $this->createMock('Zend\Form\ElementInterface');

        $fieldset = $this->createMock('Console\Form\Software');
        $fieldset->method('get')->willReturnMap([
            ['Accept', $accept],
            ['Ignore', $ignore],
        ]);

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->method('__invoke')->willReturnMap([
            [$accept, null, null, null, 'ACCEPT'],
            [$ignore, null, null, null, 'IGNORE'],
        ]);

        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $view->method('plugin')->with('formRow')->willReturn($formRow);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['renderButtons'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            "<div class='textcenter'>\n$buttons</div>\n",
            $helper->renderButtons($fieldset, $filter)
        );
    }

    public function testRenderSoftwareFieldset()
    {
        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');

        $checkbox1 = $this->createMock('\Zend\Form\ElementInterface');
        $checkbox2 = $this->createMock('\Zend\Form\ElementInterface');

        $fieldset = $this->createMock('Zend\Form\FieldsetInterface');
        $fieldset->method('get')
                 ->withConsecutive(
                     ['c29mdHdhcmVfbmFtZTE='], // 'software_name1'
                     ['c29mdHdhcmVfbmFtZTI='] // 'software_name2'
                 )->willReturnOnConsecutiveCalls($checkbox1, $checkbox2);

        $software = [
            ['name' => 'software_name1', 'num_clients' => 2],
            ['name' => 'software_name2', 'num_clients' => 1],
        ];

        $sorting = ['order' => 'current_order', 'direction' => 'current_direction'];

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->expects($this->at(0))->method('isTranslatorEnabled')->willReturn('translatorEnabled');
        $formRow->expects($this->at(1))->method('setTranslatorEnabled')->with(false);
        $formRow->expects($this->at(2))->method('__invoke')
                                       ->with($checkbox1, \Zend\Form\View\Helper\FormRow::LABEL_APPEND)
                                       ->willReturn('checkbox1');
        $formRow->expects($this->at(3))->method('__invoke')
                                       ->with($checkbox2, \Zend\Form\View\Helper\FormRow::LABEL_APPEND)
                                       ->willReturn('checkbox2');
        $formRow->expects($this->at(4))->method('setTranslatorEnabled')->with('translatorEnabled');

        $translate = $this->createMock('Zend\I18n\View\Helper\Translate');
        $translate->method('__invoke')
                  ->withConsecutive(
                      ['Name', null, null],
                      ['Count', null, null]
                  )->willReturnOnConsecutiveCalls('NAME', 'COUNT');

        $consoleUrl = $this->createMock('Library\View\Helper\HtmlElement');
        $consoleUrl->method('__invoke')
                   ->withConsecutive(
                       [
                        'client',
                        'index',
                        [
                            'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                            'jumpto' => 'software',
                            'filter' => 'Software',
                            'search' => 'software_name1',
                        ]
                       ],
                       [
                        'client',
                        'index',
                        [
                            'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                            'jumpto' => 'software',
                            'filter' => 'Software',
                            'search' => 'software_name2',
                        ]
                       ]
                   )->willReturnOnConsecutiveCalls('url1', 'url2');

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->method('__invoke')
                    ->withConsecutive(
                        ['a', 2, ['href' => 'url1']],
                        ['a', 1, ['href' => 'url2']]
                    )->willReturnOnConsecutiveCalls('link1', 'link2');

        $table = $this->createMock('Console\View\Helper\Table');
        $table->method('prepareHeaders')
              ->with(['name' => 'NAME', 'num_clients' => 'COUNT'], $sorting)
              ->willReturn(['name' => 'header_name', 'num_clients' => 'header_count']);
        $table->method('row')
              ->withConsecutive(
                  [
                    ['name' => '<input type="checkbox" class="checkAll">header_name', 'num_clients' => 'header_count'],
                    true,
                    [],
                    null,
                  ],
                  [['name' => 'checkbox1', 'num_clients' => 'link1'], false, ['num_clients' => 'textright'], null],
                  [['name' => 'checkbox2', 'num_clients' => 'link2'], false, ['num_clients' => 'textright'], null]
              )
              ->willReturnOnConsecutiveCalls('<header>', '<row1>', '<row2>');
        $table->method('tag')->with('<header><row1><row2>')->willReturn('softwareFieldset');

        $view->method('plugin')->willReturnMap([
            ['formRow', null, $formRow],
            ['translate', null, $translate],
            ['table', null, $table],
            ['htmlElement', null, $htmlElement],
            ['consoleUrl', null, $consoleUrl],
        ]);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['renderSoftwareFieldset'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            'softwareFieldset',
            $helper->renderSoftwareFieldset($fieldset, $software, $sorting)
        );
    }
}

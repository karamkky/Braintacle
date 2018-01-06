<?php
/**
 * Controller for all software-related actions.
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

/**
 * Controller for all software-related actions.
 */
class SoftwareController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Software manager
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Software filter form
     * @var \Console\Form\SoftwareFilter
     */
    protected $_form;

    /**
     * Constructor
     *
     * @param \Model\SoftwareManager $softwareManager
     * @param \Console\Form\SoftwareFilter $form
     */
    public function __construct(\Model\softwareManager $softwareManager, \Console\Form\SoftwareFilter $form)
    {
        $this->_softwareManager = $softwareManager;
        $this->_form = $form;
    }

    /**
     * Display filter form and all software according to selected filter (default: accepted)
     *
     * @return array filter, form, software[]
     */
    public function indexAction()
    {
        $filter = $this->params()->fromQuery('filter', 'accepted');
        $this->_form->setFilter($filter);
        $this->_form->remove('_csrf');
        $session = new \Zend\Session\Container('ManageSoftware');
        $session->filter = $filter;

        $order = $this->getOrder('name');
        return array(
            'filter' => $filter,
            'form' => $this->_form,
            'software' => $this->_softwareManager->getSoftware(
                array(
                    'Os' => $this->params()->fromQuery('os', 'windows'),
                    'Status' => $filter,
                ),
                $order['order'],
                $order['direction']
            ),
            'order' => $order,
        );
    }

    /**
     * Ignore selected software
     *
     * @return mixed array(name) or redirect response
     */
    public function ignoreAction()
    {
        return $this->_manage(false);
    }

    /**
     * Accept selected software
     *
     * @return mixed array(name) or redirect response
     */
    public function acceptAction()
    {
        return $this->_manage(true);
    }

    /**
     * Accept or ignore selected software
     *
     * @param bool $display Display status to set
     * @return mixed array(name) or redirect response
     */
    protected function _manage($display)
    {
        $name = $this->params()->fromQuery('name');
        if ($name === null) {
            throw new \RuntimeException('Missing name parameter');
        }
        if ($this->getRequest()->isGet()) {
            return array('name' => $name); // Display confirmation form
        } else {
            if ($this->params()->fromPost('yes')) {
                $this->_softwareManager->setDisplay($name, $display);
            }
            $session = new \Zend\Session\Container('ManageSoftware');
            return $this->redirectToRoute('software', 'index', array('filter' => $session->filter));
        }
    }
}

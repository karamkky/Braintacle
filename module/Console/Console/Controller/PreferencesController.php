<?php
/**
 * Controller for managing preferences
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

namespace Console\Controller;

/**
 * Controller for managing preferences
 */
class PreferencesController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Form manager
     * @var \Zend\Form\FormElementManager
     */
    protected $_formManager;

    /**
     * CustomFields prototype
     * @var \Model_UserDefinedInfo
     */
    protected $_customFields;

    /**
     * DeviceType prototype
     * @var \Model_NetworkDeviceType
     */
    protected $_deviceType;

    /**
     * RegistryValue prototype
     * @var \Model_RegistryValue
     */
    protected $_registryValue;

    /**
     * Constructor
     *
     * @param \Zend\Form\FormElementManager $formManager
     * @param \Model_UserDefinedInfo $customFields
     * @param \Model_NetworkDeviceType $deviceType
     * @param \Model_RegistryValue $registryValue
     */
    public function __construct(
        \Zend\Form\FormElementManager $formManager,
        \Model_UserDefinedInfo $customFields,
        \Model_NetworkDeviceType $deviceType,
        \Model_RegistryValue $registryValue
    )
    {
        $this->_formManager = $formManager;
        $this->_customFields = $customFields;
        $this->_deviceType = $deviceType;
        $this->_registryValue = $registryValue;
    }

    /**
     * Redirect to first page
     *
     * @return \Zend\Http\Response redirect response
     */
    public function indexAction()
    {
        return $this->redirectToRoute('preferences', 'display');
    }

    /**
     * Show "Display" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function displayAction()
    {
        return $this->_useForm('Console\Form\Preferences\Display');
    }

    /**
     * Show "Inventory" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function inventoryAction()
    {
        return $this->_useForm('Console\Form\Preferences\Inventory');
    }

    /**
     * Show "Agent" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function agentAction()
    {
        return $this->_useForm('Console\Form\Preferences\Agent');
    }

    /**
     * Show "Packages" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function packagesAction()
    {
        return $this->_useForm('Console\Form\Preferences\Packages');
    }

    /**
     * Show "Download" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function downloadAction()
    {
        return $this->_useForm('Console\Form\Preferences\Download');
    }

    /**
     * Show "Network scanning" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function networkscanningAction()
    {
        return $this->_useForm('Console\Form\Preferences\NetworkScanning');
    }

    /**
     * Show "Groups" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function groupsAction()
    {
        return $this->_useForm('Console\Form\Preferences\Groups');
    }

    /**
     * Show "Raw data" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function rawdataAction()
    {
        return $this->_useForm('Console\Form\Preferences\RawData');
    }

    /**
     * Show "Filters" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function filtersAction()
    {
        return $this->_useForm('Console\Form\Preferences\Filters');
    }

    /**
     * Show "System" page
     *
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    public function systemAction()
    {
        return $this->_useForm('Console\Form\Preferences\System');
    }

    /**
     * Provide form to manage custom fields
     *
     * @return array Array(form)
     */
    public function customfieldsAction()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\DefineFields');
        if ($this->getRequest()->isPost() and $form->isValid($this->params()->fromPost())) {
            $form->process();
            return $this->redirectToRoute('preferences', 'customfields');
        } else {
            return array('form' => $form);
        }
    }

    /**
     * Delete a custom field definition
     *
     * URL parameter: 'name'
     * @return array|\Zend\Http\Response array(field) or redirect response
     */
    public function deletefieldAction()
    {
        $field = $this->params()->fromQuery('name');
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_customFields->deleteField($field);
            }
            return $this->redirectToRoute('preferences', 'customfields');
        } else {
            return array('field' => $field);
        }
    }

    /**
     * Provide form to manage network device types
     *
     * @return array|\Zend\Http\Response Array(form) or redirect response
     */
    public function networkdevicesAction()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\NetworkDeviceTypes');
        if ($this->getRequest()->isPost() and $form->isValid($this->params()->fromPost())) {
            $form->process();
            return $this->redirectToRoute('network', 'index');
        } else {
            return array('form' => $form);
        }
    }

    /**
     * Delete a network device type definition
     *
     * URL parameter: 'id'
     * @return array|\Zend\Http\Response Array(description) or redirect response
     */
    public function deletedevicetypeAction()
    {
        $type = $this->_deviceType->fetchById($this->params()->fromQuery('id'));
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $type->delete();
            }
            return $this->redirectToRoute('preferences', 'networkdevices');
        } else {
            return array('description' => $type['Description']);
        }
    }

    /**
     * Provide form to manage inventoried registry values
     *
     * @return array|\Zend\Http\Response Array(form) or redirect response
     */
    public function registryvaluesAction()
    {
        $form = $this->_formManager->get('Console\Form\ManageRegistryValues');
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $form->process();
                return $this->redirectToRoute('preferences', 'registryvalues');
            }
        }
        return array('form' => $form);
    }

    /**
     * Delete a registry value definition
     *
     * URL parameter: 'id'
     * @return array|\Zend\Http\Response Array(name) or redirect response
     */
    public function deleteregistryvalueAction()
    {
        $value = $this->_registryValue->fetchById($this->params()->fromQuery('id'));
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $value->delete();
            }
            return $this->redirectToRoute('preferences', 'registryvalues');
        } else {
            return array('name' => $value['Name']);
        }
    }

    /**
     * Standard preferences handling via preferences form subclass
     *
     * @param string $name Name of the form service
     * @return \Zend\View\Model\ViewModel View model for "form.php" template
     */
    protected function _useForm($name)
    {
        $form = $this->_formManager->getServiceLocator()->get($name);
        if ($this->getRequest()->isGet()) {
            $form->loadDefaults();
        } else {
            $form->process($this->params()->fromPost());
        }
        return $this->printForm($form);
    }
}

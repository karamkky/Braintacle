<?php
/**
 * Search form
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

namespace Console\Form;

use Zend\Form\Element;

/**
 * Search form
 *
 * The form requires the following options to be set before invoking init():
 *
 * - **translator:** Translator
 * - **registryValue:** \Model_RegistryValue prototype
 * - **customFields:** \Model_UserDefinedInfo prototype
 *
 * The factory injects these automatically.
 */
class Search extends Form
{
    /**
     * All available filters with their translated labels.
     * @var string[]
     */
    protected $_filters; // Populated by init()

    /**
     * Filter types (default: text)
     *
     * Custom field types are added by init() if necessary.
     * @var string[]
     */
    protected $_types = array(
        'CpuClock' => 'integer',
        'CpuCores' => 'integer',
        'InventoryDate' => 'date',
        'LastContactDate' => 'date',
        'PhysicalMemory' => 'integer',
        'SwapMemory' => 'integer',
        'Volume.Size' => 'integer',
        'Volume.FreeSpace' => 'integer',
    );

    /**
     * Value options for ordinal searches (integer, float, date)
     * @var string[]
     */
    protected $_operatorsOrdinal = array(
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'le' => '<=',
        'ge' => '>=',
        'gt' => '>',
    );

    /**
     * Value options for text searches
     * @var string[]
     */
    protected $_operatorsText; // Populated by init()

    /** {@inheritdoc} */
    public function init()
    {
        $translator = $this->getOption('translator');

        $this->_filters = array(
            'Name' => $translator->translate('Computer name'),
            'UserName' => $translator->translate('User name'),
            'Windows.UserDomain' => $translator->translate('User domain'),
            'Workgroup' => $translator->translate('Workgroup'),
            'OsName' => $translator->translate('OS name'),
            'OsVersionNumber' => $translator->translate('OS version number'),
            'OsVersionString' => $translator->translate('OS version string'),
            'OsComment' => $translator->translate('OS comment'),
            'Windows.ProductKey' => $translator->translate('Windows product key'),
            'Windows.ManualProductKey' => $translator->translate('Windows product key (manual)'),
            'Software.Name' => $translator->translate('Software: Name'),
            'Software.Version' => $translator->translate('Software: Version'),
            'Software.Publisher' => $translator->translate('Software: Publisher'),
            'Software.Comment' => $translator->translate('Software: Comment'),
            'Software.InstallLocation' => $translator->translate('Software: Install location'),
            'MsOfficeProduct.ProductKey' => $translator->translate('MS Office product key'),
            'MsOfficeProduct.ProductId' => $translator->translate('MS Office product ID'),
            'InventoryDate' => $translator->translate('Last inventory'),
            'LastContactDate' => $translator->translate('Last contact'),
            'CpuType' => $translator->translate('CPU type'),
            'CpuClock' => $translator->translate('CPU clock (MHz)'),
            'CpuCores' => $translator->translate('CPU cores'),
            'PhysicalMemory' => $translator->translate('Physical memory'),
            'SwapMemory' => $translator->translate('Swap memory'),
            'Manufacturer' => $translator->translate('Manufacturer'),
            'Model' => $translator->translate('Model'),
            'Serial' => $translator->translate('Serial number'),
            'AssetTag' => $translator->translate('Asset tag'),
            'BiosVersion' => $translator->translate('BIOS version'),
            'BiosDate' => $translator->translate('BIOS date'),
            'Volume.Size' => $translator->translate('Volume size (MB)'),
            'Volume.FreeSpace' => $translator->translate('Volume free space (MB)'),
            'DnsServer' => $translator->translate('DNS server'),
            'DefaultGateway' => $translator->translate('Default gateway'),
            'NetworkInterface.IpAddress' => $translator->translate('IP address'),
            'NetworkInterface.MacAddress' => $translator->translate('MAC address'),
            'NetworkInterface.Subnet' => $translator->translate('Network address'),
            'NetworkInterface.Netmask' => $translator->translate('Network Mask'),
            'Printer.Name' => $translator->translate('Printer name'),
            'Printer.Port' => $translator->translate('Printer port'),
            'Printer.Driver' => $translator->translate('Printer driver'),
            'OcsAgent' => $translator->translate('OCS agent'),
            'Display.Manufacturer' => $translator->translate('Monitor: manufacturer'),
            'Display.Description' => $translator->translate('Monitor: description'),
            'Display.Serial' => $translator->translate('Monitor: serial'),
            'Display.ProductionDate' => $translator->translate('Monitor: production date'),
            'DisplayController.Name' => $translator->translate('Display controller'),
            'DisplayController.Memory' => $translator->translate('GPU memory'),
            'Modem.Name' => $translator->translate('Modem'),
            'AudioDevice.Name' => $translator->translate('Audio device'),
            'Controller.Name' => $translator->translate('Controller'),
            'Port.Name' => $translator->translate('Port name'),
            'ExtensionSlot.Name' => $translator->translate('Extension slot'),
        );

        // Append filters and labels for registry values/data
        foreach ($this->getOption('registryValue')->fetchAll() as $regValue) {
            $name = $regValue['Name'];
            $this->_filters["Registry.$name"] = "Registry: $name";
        }

        // Append filters and labels for user defined info
        $template = $translator->translate('User defined: %s');
        foreach ($this->getOption('customFields')->getPropertyTypes() as $name => $type) {
            $key = "UserDefinedInfo.$name";
            switch ($type) {
                case 'text':
                case 'clob':
                    break;
                case 'integer':
                case 'float':
                case 'date':
                    $this->_types[$key] = $type;
                    break;
                default:
                    throw new \UnexpectedValueException('Unsupported datatype: ' . $type);
            }
            if ($name == 'TAG') {
                $label = $translator->translate('Category');
            } else {
                $label = $name;
            }
            $this->_filters[$key] = sprintf($template, $label);
        }

        $filter = new Element\Select('filter');
        $filter->setLabel('Search for')
               ->setAttribute('onchange', 'filterChanged();') // Set operators for selected filter
               ->setValueOptions($this->_filters);
        $this->add($filter);

        $search = new Element\Text('search');
        $search->setLabel('Value');
        $this->add($search);

        // Operators dropdown. Options are set by JS depending on filter type.
        // Since valid options are known only after submission, the internal
        // InArray validator must be disabled and replaced by a callback.
        $operator = new Element\Select('operator');
        $operator->setDisableInArrayValidator(true)
                 ->setLabel('Operator');
        $this->add($operator);

        $invert = new Element\Checkbox('invert');
        $invert->setLabel('Invert results');
        $this->add($invert);

        $submit = new \Library\Form\Element\Submit('customSearch');
        $submit->setText('Search');
        $this->add($submit);

        $inputFilter = new \Zend\InputFilter\InputFilter;
        $inputFilter->add(
            array(
                'name' => 'search',
                'continue_if_empty' => true, // Have empty value processed by callback validator
                'filters' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'filterSearch'),
                        ),
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateSearch'),
                        ),
                    ),
                ),
            )
        );
        $inputFilter->add(
            array(
                'name' => 'operator',
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateOperator'),
                        ),
                    ),
                ),
            )
        );
        $this->setInputFilter($inputFilter);

        $this->_operatorsText = array(
            'like' => $translator->translate('Substring match, wildcards "?" and "*" allowed'),
            'eq' => $translator->translate('Exact match'),
        );
    }

    /**
     * Filter callback for search input
     *
     * @internal
     * @param string $value
     * @return mixed normalized input depending on filter type
     */
    public function filterSearch($value)
    {
        return $this->normalize(
            $this->_getTypeFromFilter($this->get('filter')->getValue()),
            $value
        );
    }

    /**
     * Validator callback for search input
     *
     * @internal
     * @param string $value
     * @param array $context
     * @return bool TRUE if $value is a valid string/integer/float/date
     * @throws \LogicException if context does not contain filter
     */
    public function validateSearch($value, $context)
    {
        if (!isset($context['filter'])) {
            throw new \LogicException('No filter submitted');
        }
        return $this->validateType($this->_getTypeFromFilter($context['filter']), $value);
    }

    /**
     * Validator callback for operator input
     *
     * @internal
     * @param string $value
     * @param array $context
     * @return bool TRUE if $value is valid for the selected filter type
     * @throws \LogicException if context does not contain filter
     */
    public function validateOperator($value, $context)
    {
        if (!isset($context['filter'])) {
            throw new \LogicException('No filter submitted');
        }
        if ($this->_getTypeFromFilter($context['filter']) == 'text') {
            $operators = $this->_operatorsText;
        } else {
            $operators = $this->_operatorsOrdinal;
        }
        return isset($operators[$value]);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['search'] = $this->localize($this->_getTypeFromFilter($data['filter']), @$data['search']);
        return parent::setData($data);
    }

    /**
     * Get the datatype for a specific filter
     *
     * @param string $filter Filter name
     * @return string datatype
     * @throws \InvalidArgumentException if the filter name is invalid
     **/
    protected function _getTypeFromFilter($filter)
    {
        if (!isset($this->_filters[$filter])) {
            throw new \InvalidArgumentException('Invalid filter: ' . $filter);
        }

        if (isset($this->_types[$filter])) {
            return $this->_types[$filter];
        } else {
            return 'text';
        }
    }

    /**
     * Render form
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @return string
     */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $view->headScript()->captureStart();
        ?>

        // Filter types
        var types = <?php print json_encode($this->_types); ?>;

        /**
         * Event handler for Filter combobox
         *
         * Sets options for "operators" element according to selected filter.
         */
        function filterChanged()
        {
            var elements = document.getElementById('form_search').elements;
            var operators;
            switch (types[elements['filter'].value]) {
                case 'integer':
                case 'float':
                case 'date':
                    operators = <?php print json_encode($this->_operatorsOrdinal); ?>;
                    break;
                default:
                    operators = <?php print json_encode($this->_operatorsText); ?>;
            }
            var options = elements['operator'].options;
            options.length = 0;
            for (var value in operators) {
                options.add(new Option(operators[value], value));
            }
        }

        <?php
        $view->headScript()->captureEnd();

        $onload = $view->placeholder('BodyOnLoad');
        $onload->append('filterChanged()');

        // Set operator value manually because the element creation code does
        // not know it's valid and ignores it.
        $initialOperator = $this->get('operator')->getValue();
        if ($initialOperator) {
            $onload->append(
                sprintf(
                    'document.getElementById("form_search").elements["operator"].value = %s',
                    json_encode($initialOperator)
                )
            );
        }
        return parent::render($view);
    }
}

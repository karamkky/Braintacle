<?php
/**
 * Form for creating a package
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

namespace Console\Form\Package;

use Zend\Form\Element;

/**
 * Form for creating a package
 *
 * The provided fields match the package property names. The packageManager
 * option must be set to a \Model\Package\PackageManager instance before init()
 * is called. The factory does this automatically.
 */
class Build extends \Console\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
        $inputFilter = new \Zend\InputFilter\InputFilter;
        $integerFilter = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'normalize'),
                'callback_params' => 'integer',
            )
        );
        $integerValidator = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'validateInteger'),
            )
        );

        // Package name
        $name = new Element\Text('Name');
        $name->setLabel('Name');
        $this->add($name);
        $inputFilter->add(
            array(
                'name' => 'Name',
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255),
                    ),
                    array(
                        'name' => 'Library\Validator\NotInArray',
                        'options' => array(
                            'haystack' => $this->getOption('packageManager')->getAllNames(),
                            'caseSensitivity' => \Library\Validator\NotInArray::CASE_INSENSITIVE,
                        ),
                    ),
                ),
            )
        );

        // Comment
        $comment = new Element\Textarea('Comment');
        $comment->setLabel('Comment');
        $this->add($comment);
        $inputFilter->add(
            array(
                'name' => 'Comment',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            )
        );

        // Platform combobox
        $platform = new Element\Select('Platform');
        $platform->setLabel('Platform')
                 ->setAttribute('type', 'select_untranslated')
                 ->setValueOptions(
                     array(
                        'windows' => 'Windows',
                        'linux' => 'Linux',
                        'mac' => 'MacOS'
                     )
                 );
        $this->add($platform);

        // Action combobox
        // Translate labels manually to let xgettext recognize them
        $action = new Element\Select('DeployAction');
        $action->setLabel('Action')
               ->setAttribute('onchange', 'changeParam()')
               ->setValueOptions(
                   array(
                        'launch' => $this->_('Download package, execute command, retrieve result'),
                        'execute' => $this->_('Optionally download package, execute command'),
                        'store' => $this->_('Just download package to target path'),
                   )
               );
        $this->add($action);

        // Command line or target path for action
        // Label is set by JavaScript code.
        $actionParam = new Element\Text('ActionParam');
        $this->add($actionParam);
        $inputFilter->add(
            array(
                'name' => 'ActionParam',
                'required' => true,
            )
        );

        // Upload file
        $file = new Element\File('File');
        $file->setLabel('File');
        $this->add($file);
        $inputFilter->add(array('name' => 'File')); // Requirement is set in isValid()

        // Priority combobox
        $priority = new \Library\Form\Element\SelectSimple('Priority');
        $priority->setValueOptions(range(0, 10))
                 ->setLabel('Priority (0: exclusive, 10: lowest)');
        $this->add($priority);

        // Maximum fragment size.
        $maxFragmentSize = new Element\Text('MaxFragmentSize');
        $maxFragmentSize->setAttribute('size', '8')
                        ->setLabel('Maximum fragment size (kB)');
        $this->add($maxFragmentSize);
        $inputFilter->add(
            array(
                'name' => 'MaxFragmentSize',
                'required' => false,
                'filters' => array($integerFilter),
                'validators' => array($integerValidator),
            )
        );

        // Warn user before installation
        $warn = new Element\Checkbox('Warn');
        $warn->setLabel('Warn user')
             ->setAttribute('id', 'form_package_build_warn')
             ->setAttribute('onchange', 'toggleWarn()');
        $this->add($warn);

        // Message to display to user before installation
        $warnMessage = new Element\Textarea('WarnMessage');
        $warnMessage->setLabel('Message');
        $this->add($warnMessage);
        $inputFilter->add(
            array(
                'name' => 'WarnMessage',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            )
        );

        // Countdown before installation starts automatically
        $warnCountdown = new Element\Text('WarnCountdown');
        $warnCountdown->setAttribute('size', '5')
                      ->setLabel('Countdown (seconds)');
        $this->add($warnCountdown);
        $inputFilter->add(
            array(
                'name' => 'WarnCountdown',
                'required' => false,
                'filters' => array($integerFilter),
                'validators' => array($integerValidator),
            )
        );

        // Allow user abort
        $warnAllowAbort = new Element\Checkbox('WarnAllowAbort');
        $warnAllowAbort->setLabel('Allow abort by user');
        $this->add($warnAllowAbort);

        // Allow user delay
        $warnAllowDelay = new Element\Checkbox('WarnAllowDelay');
        $warnAllowDelay->setLabel('Allow delay by user');
        $this->add($warnAllowDelay);

        // Message to display to user after deployment
        $postInstMessage = new Element\Textarea('PostInstMessage');
        $postInstMessage->setLabel('Post-installation message');
        $this->add($postInstMessage);
        $inputFilter->add(
            array(
                'name' => 'PostInstMessage',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            )
        );

        // Submit button
        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setText('Build');
        $this->add($submit);

        $this->setInputFilter($inputFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['MaxFragmentSize'] = $this->localize(@$data['MaxFragmentSize'], 'integer');
        $data['WarnCountdown'] = $this->localize(@$data['WarnCountdown'], 'integer');
        return parent::setData($data);
    }

    /** {@inheritdoc} */
    public function isValid()
    {
        $this->getInputFilter()->get('File')->setRequired(@$this->data['DeployAction'] != 'execute');
        return parent::isValid();
    }

    /**
     * Validator callback for integer fields
     * @internal
     */
    public function validateInteger($value, $context)
    {
        if ($value === '') {
            return true;
        } else {
            return $this->validateType($value, $context, 'integer');
        }
    }

    /** {@inheritdoc} */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $commandLine = $view->translate('Command line');
        $labels = array(
            'launch' => $commandLine,
            'execute' => $commandLine,
            'store' => $view->translate('Target Path'),
        );
        $labels = '    var actionParamLabels = ' . json_encode($labels) . ";\n";

        $view->headScript()->captureStart();
        print $labels;
        ?>

        /**
         * Hide or display a block element.
         */
        function display(name, display)
        {
            document.getElementsByName(name)[0].parentNode.style.display = display ? 'table-row' : 'none';
        }

        /*
         * Event handler for Action combobox. Also called for form initialization.
         * Changes label of parameter input field according to selected action.
         */
        function changeParam()
        {
            var label = actionParamLabels[document.getElementsByName('DeployAction')[0].value];
            document.getElementsByName('ActionParam')[0].parentNode.getElementsByTagName('span')[0].innerHTML = label;
        }

        /*
         * Event handler for Warn checkbox. Also called for form initialization.
         * Hides or displays Warn* fields according to checked state.
         */
        function toggleWarn()
        {
            var checked = document.getElementById('form_package_build_warn').checked;
            display('WarnMessage', checked);
            display('WarnCountdown', checked);
            display('WarnAllowAbort', checked);
            display('WarnAllowDelay', checked);
        }

        <?php
        $view->headScript()->captureEnd();

        $view->placeholder('BodyOnLoad')->append('changeParam()');
        $view->placeholder('BodyOnLoad')->append('toggleWarn()');
        $view->placeholder('BodyOnLoad')->append('document.getElementsByName("Name")[0].focus()');

        return parent::render($view);
    }
}

<?php
/**
 * Form for display/setting of 'raw data' preferences
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
 *
 * @package Forms
 */
/**
 * Form for display/setting of 'raw data' preferences
 * @package Forms
 */
class Form_Preferences_RawData extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'saveRawData' => 'bool',
        'saveDir' => 'text',
        'saveFormat' => null, // populated by init()
        'saveOverwrite' => 'bool',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_types['saveFormat'] = array(
            'XML' => $translate->_('uncompressed XML'),
            'OCS' => $translate->_('zlib compressed XML')
        );
        $this->_labels = array(
            'saveRawData' => $translate->_(
                'Save incoming raw inventory data'
            ),
            'saveDir' => $translate->_(
                'Target directory'
            ),
            'saveFormat' => $translate->_(
                'File format'
            ),
            'saveOverwrite' => $translate->_(
                'Overwrite existing files'
            ),
        );
        parent::init();
        $this->getElement('saveDir')
            ->addValidator(new Braintacle_Validate_DirectoryWritable);
    }

}

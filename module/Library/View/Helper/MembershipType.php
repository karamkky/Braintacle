<?php
/**
 * Render translated text for membership type
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Library\View\Helper;

/**
 * Render translated text for membership type
 */
class MembershipType extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Translate view helper
     * @var \Zend\I18n\View\Helper\Translate
     */
    protected $_translate;

    /**
     * Constructor
     *
     * @param \Zend\I18n\View\Helper\Translate $translate
     */
    public function __construct(\Zend\I18n\View\Helper\Translate $translate)
    {
        $this->_translate = $translate;
    }

    /**
     * Render translated text for membership type
     *
     * @param integer $type One of \Model\Client\Client::MEMBERSHIP_AUTOMATIC or \Model\Client\Client::MEMBERSHIP_ALWAYS
     * @return string Translation for either 'automatic' or 'manual'
     * @throws \InvalidArgumentException if $type is not one of the allowed values
     */
    public function __invoke($type)
    {
        // Cast to string to avoid type juggling side effects
        switch ((string) $type) {
            case (string) \Model\Client\Client::MEMBERSHIP_AUTOMATIC:
                return $this->_translate->__invoke('automatic');
            case (string) \Model\Client\Client::MEMBERSHIP_ALWAYS:
                return $this->_translate->__invoke('manual');
            default:
                throw new \InvalidArgumentException("Invalid group membership type: $type");
        }
    }
}
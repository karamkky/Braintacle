<?php
/**
 * Display virtual machines hosted on a client
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

require 'header.php';

$client = $this->client;

$headers = array(
    'Name' => $this->translate('Name'),
    'Status' => $this->translate('Status'),
    'Product' => $this->translate('Product'),
    'Type' => $this->translate('Type'),
    'Uuid' => $this->translate('UUID'),
    'GuestMemory' => $this->translate('Memory'),
);

$renderCallbacks = array(
    'GuestMemory' => function($view, $virtualMachine) {
        $mem = $view->escapeHtml($virtualMachine['GuestMemory']);
        if ($mem) {
            $mem .= ' MB';
        }
        return $mem;
    }
);

$vms = $client->getItems('VirtualMachine', $this->order, $this->direction);
if (count($vms)) {
    print $this->htmlTag(
        'h2',
        $this->translate('Virtual machines hosted on this client')
    );
    print $this->table(
        $vms,
        $headers,
        array('order' => $this->order, 'direction' => $this->direction),
        $renderCallbacks
    );
}
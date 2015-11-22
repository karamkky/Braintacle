<?php
/**
 * Display all group members
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

require('header.php');

$headers = array(
    'Name' => $this->translate('Name'),
    'UserName' => $this->translate('User'),
    'InventoryDate' => $this->translate('Last inventory'),
    'Membership' => $this->translate('Membership'),
);

$renderCallbacks = array(
    'Name' => function($view, $computer) {
        return $view->htmlTag(
            'a',
            $view->escapeHtml($computer['Name']),
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'groups',
                    array('id' => $computer['Id'])
                ),
            ),
            true
        );
    },
    'Membership' => function($view, $computer) {
        return $view->membershipType($computer['Membership']);
    }
);

print "<table class='textnormalsize'>\n";

$creationDate = $this->group['CacheCreationDate'];
printf(
    "<tr><td class='textright'>%s</td><td>%s</td></tr>\n",
    $this->translate('Last update:'),
    $creationDate ? $this->dateFormat(
        $creationDate,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::MEDIUM
    ) : '&ndash;'
);

$expirationDate = $this->group['CacheExpirationDate'];
printf(
    "<tr><td class='textright'>%s</td><td>%s</td></tr>\n",
    $this->translate('Next update:'),
    $expirationDate ? $this->dateFormat(
        $expirationDate,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::MEDIUM
    ) : '&ndash;'
);

print "</table>\n";

print $this->htmlTag(
    'p',
    sprintf(
        $this->translate('Number of computers: %d'),
        count($this->computers)
    ),
    array('class' => 'textcenter')
);

print $this->table(
    $this->computers,
    $headers,
    $this->sorting,
    $renderCallbacks
);
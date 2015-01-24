<?php
/**
 * Display list of all software
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
 *
 */

$headers = array(
    'AcceptIgnore' => '',
    'Name' => $this->translate('Name'),
    'NumComputers' => $this->translate('Count'),
);

$renderCallbacks = array (
    'AcceptIgnore' => function($view, $software) {
        $links = array();
        if ($view->filter == 'ignored' or $view->filter == 'new') {
            $links[] = $view->htmlTag(
                'a',
                $view->translate('Accept'),
                array(
                    'href' => $view->consoleUrl(
                        'software',
                        'accept',
                        array('name' => $software['RawName'])
                    ),
                ),
                true
            );
        }
        if ($view->filter == 'accepted' or $view->filter == 'new') {
            $links[] = $view->htmlTag(
                'a',
                $view->translate('Ignore'),
                array(
                    'href' => $view->consoleUrl(
                        'software',
                        'ignore',
                        array('name' => $software['RawName'])
                    ),
                ),
                true
            );
        }
        return implode(' ', $links);
    },
    'NumComputers' => function($view, $software) {
        return $view->htmlTag(
            'a',
            $software['NumComputers'],
            array(
                'href' => $view->consoleUrl(
                    'client',
                    'index',
                    array(
                        'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                        'jumpto' => 'software',
                        'filter' => 'Software',
                        'search' => $software['RawName'],
                    )
                ),
            ),
            true
        );
    }
);

print $this->form->render($this);
print $this->table(
    $this->software,
    $headers,
    $this->order,
    $renderCallbacks,
    array('NumComputers' => 'textright')
);

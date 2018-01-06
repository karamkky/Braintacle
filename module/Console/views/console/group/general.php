<?php
/**
 * Display general information about a group
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

require('header.php');

$format = "<tr>\n<td class='label'>%s</td>\n<td>%s</td>\n</tr>\n";

print "<table class='textnormalsize'>\n";

printf(
    $format,
    $this->translate('Name'),
    $this->escapeHtml($this->group['Name'])
);
printf(
    $format,
    $this->translate('ID'),
    $this->escapeHtml($this->group['Id'])
);
printf(
    $format,
    $this->translate('Description'),
    $this->escapeHtml($this->group['Description'])
);
printf(
    $format,
    $this->translate('Creation date'),
    $this->escapeHtml(
        $this->dateFormat(
            $this->group['CreationDate'],
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::MEDIUM
        )
    )
);
printf(
    $format,
    $this->translate('SQL query'),
    $this->htmlElement(
        'code',
        $this->escapeHtml($this->group['DynamicMembersSql'])
    )
);

print "</table\n>";

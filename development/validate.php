#!/usr/bin/php
<?php
/**
 * Validate code formatting
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

error_reporting(-1);

$baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
$cmd = array(
    escapeshellarg(realpath(__DIR__ . '/../vendor/bin/phpcs')),
    '-n', // suppress warnings
    '--standard=PSR2',
    escapeshellarg($baseDir . 'development'),
    escapeshellarg($baseDir . 'module'),
    escapeshellarg($baseDir . 'public'),
    escapeshellarg($baseDir . 'tools'),
);
passthru(implode(' ', $cmd), $result);
exit($result);

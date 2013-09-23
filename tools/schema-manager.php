#!/usr/bin/php
<?php
/**
 * Update the database schema and adjust some data to the new schema.
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * @package Tools
 */
/**
 * This script updates Braintacle's database schema.
 *
 * Run this script every time the schema has changed. It is safe to run it more
 * than once, even if the schema has not changed. However, it won't hurt to
 * back up your database first.
 */

// All paths are relative to this script's parent directory
$basepath = realpath(dirname(__DIR__));

require_once "$basepath/module/Library/Application.php";
\Library\Application::init('Cli');

// Parse command line. This needs to be done before initializing the application
// because that would set APPLICATION_ENV, but that could be overridden in the
// command line.
require_once 'Zend/Console/Getopt.php';
require_once 'Zend/Console/Getopt/Exception.php';
$cmdLine = new Zend_Console_Getopt(
    array(
        'environment|e=w' => 'Application environment (default: production)',
        'force|f' => 'force update',
    )
);
try {
    $cmdLine->parse();
    if ($cmdLine->getRemainingArgs()) {
        throw new Zend_Console_Getopt_Exception('', $cmdLine->getUsageMessage());
    }
} catch(Zend_Console_Getopt_Exception $exception) {
    print $exception->getUsageMessage();
    exit(1);
}

// Set up application environment
$environment = $cmdLine->environment;
if (!$environment) {
    $environment = 'production';
}
putenv("APPLICATION_ENV=$environment");

// Set up logger
// TODO: remove ZF1 logger when no longer used by legacy schema manager
$oldWriter = new Zend_Log_Writer_Stream('php://stderr');
$oldFormatter = new Zend_Log_Formatter_Simple('%priorityName%: %message%' . PHP_EOL);
$oldWriter->setFormatter($oldFormatter);
$oldLogger = new Zend_Log($oldWriter);
$formatter = new \Zend\Log\Formatter\Simple('%priorityName%: %message%');
$writer = new \Zend\Log\Writer\Stream('php://stderr');
$writer->setFormatter($formatter);
$logger = \Library\Application::getService('Library\Logger');
$logger->addWriter($writer);

// Create Schema manager object
Braintacle_MDB2::setErrorReporting();
$manager = new Braintacle_SchemaManager($oldLogger);
$schemaManager = new \Database\SchemaManager($manager);

$isCompatible = $manager->isOcsCompatible();
if ($cmdLine->force or (!$isCompatible and $manager->isUpdateRequired())) {
    if ($isCompatible) {
        $logger->warn(
            'Schema update forced. Database will no longer be compatible with OCS Inventory NG.'
        );
        $logger->warn(
            'The version that comes bundled with Braintacle will continue to work.'
        );
    }
    // Update the database automatically
    $schemaManager->updateAll();
    $logger->info('Database successfully updated.');
} else {
    if ($isCompatible) {
        $logger->notice(
            'The current database is still compatible with OCS Inventory NG.'
        );
        $logger->notice(
            'Use --force to update anyway, but you will lose compatibility.'
        );
    } else {
        $logger->info('Database is already up to date. Use --force to update anyway.');
    }
}

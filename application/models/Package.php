<?php
/**
 * Class representing a package which can be deployed to a computer
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
 * @package Models
 */
/** A single package which can be deployed to a computer
 *
 * This class provides an interface to all related database entries, the
 * download directory and the files stored within this directory (XML metafile
 * and the package fragments).
 *
 * The procedures of building and activating a package are merged into a single
 * step. This is limited to a single download location per package.
 *
 *
 * The following properties are mapped to a column in the download_available
 * table. Some of these will also be used in the XML metafile.
 *
 * - <b>Name:</b> name to uniquely identify package
 * - <b>Timestamp:</b> Timestamp of package creation
 * - <b>Priority:</b> download priority (0-10)
 * - <b>NumFragments:</b> number of download fragments - ignored by {@link build()}
 * - <b>Size:</b> download size - ignored by {@link build()}
 * - <b>Platform:</b> one of 'windows', 'linux' or 'mac'
 * - <b>Comment:</b> comment
 *
 * The following properties are mapped to a column in the download_enable table.
 *
 * - <b>EnabledId:</b> primary key for download_enable
 *
 * The following readonly properties are generated by {@link fetchAll()}.
 *
 * - <b>NumNonnotified:</b> number of clients waiting for notification
 * - <b>NumSuccess:</b> number of clients with successful deployment
 * - <b>NumNotified:</b> number of clients currently downloading/installing package
 * - <b>NumError:</b> number of clients with unsuccessful deployment
 *
 * The following properties are mapped to an XML attribute.
 *
 * - <b>Hash:</b> hash of assembled package
 * - <b>DeployAction:</b> one of 'store', 'execute', 'launch'
 * - <b>ActionParam:</b> path for storage or command to execute, depending on action
 * - <b>Warn:</b> Whether the user should be notified before deployment
 * - <b>WarnMessage:</b> Message to display before deployment
 * - <b>WarnCountdown:</b> Timeout in seconds before deployment starts
 * - <b>WarnAllowAbort:</b> Whether the user should be allowed to abort
 * - <b>WarnAllowDelay:</b> Whether the user should be allowed to delay
 * - <b>UserActionRequired:</b> Whether the user should be notified after deployment
 * - <b>PostInstMessage:</b> Message to display after deployment
 *
 * The following Attributes are only used by {@link build()}.
 *
 * - <b>MaxFragmentSize:</b> maximum size of a single fragment
 * - <b>FileName:</b> name of uploaded file, used only for ZIP file creation
 * - <b>FileLocation:</b> full path to uploaded file. May be deleted by build()!
 * - <b>FileType:</b> MIME type of uploaded file
 * @package Models
 */
class Model_Package extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        'Name' => 'name',
        'Timestamp' => 'fileid',
        'Priority' => 'priority',
        'NumFragments' => 'fragments',
        'Size' => 'size',
        'Platform' => 'osname',
        'Comment' => 'comment',
        'EnabledId' => 'id',
        'NumNonnotified' => 'num_nonnotified',
        'NumSuccess' => 'num_success',
        'NumNotified' => 'num_notified',
        'NumError' => 'num_error',
        'Hash' => 'DIGEST',
        'DeployAction' => 'ACT',
        'ActionParam' => 'actionParam',
        'Warn' => 'NOTIFY_USER',
        'WarnMessage' => 'NOTIFY_TEXT',
        'WarnCountdown' => 'NOTIFY_COUNTDOWN',
        'WarnAllowAbort' => 'NOTIFY_CAN_ABORT',
        'WarnAllowDelay' => 'NOTIFY_CAN_DELAY',
        'UserActionRequired' => 'NEED_DONE_ACTION',
        'PostInstMessage' => 'NEED_DONE_ACTION_TEXT',
        'MaxFragmentSize' => 'maxFragmentSize',
        'FileName' => 'fileName',
        'FileLocation' => 'fileLocation',
        'FileType' => 'fileType',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Timestamp' => 'timestamp',
        'Priority' => 'integer',
        'NumFragments' => 'integer',
        'Size' => 'integer',
        'Comment' => 'clob',
        'EnabledId' => 'integer',
        'NumNonnotified' => 'integer',
        'NumSuccess' => 'integer',
        'NumNotified' => 'integer',
        'NumError' => 'integer',
        'Warn' => 'boolean',
        'WarnCountdown' => 'integer',
        'WarnAllowAbort' => 'boolean',
        'WarnAllowDelay' => 'boolean',
        'UserActionRequired' => 'boolean',
        'MaxFragmentSize' => 'integer',
    );

    /**
     * Array with error messages
     * @var array
     */
    protected $_errors = array();

    /**
     * whether _build() has created the download directory
     * @var bool
     */
    protected $_directoryCreated;

    /**
     * whether _build() has created the package in database
     * @var bool
     */
    protected $_writtenToDb;

    /**
     * whether _build() has activated the package in database
     * @var bool
     */
    protected $_activated;

    /**
     * Set to TRUE when package is in a "dirty" state and needs cleanup
     * @var bool
     */
    protected $_needCleanup = false;

    /**
     * Destructor
     *
     * Performs cleanup even when instance gets destroyed prematurely.
     */
    function __destruct()
    {
        $this->_cleanup();
    }

    /**
     * Retrieve a property by its logical name
     *
     * Mangles platform names from raw database values to nicer abstract values.
     * @return mixed property value
     */
    public function getProperty($property, $rawValue=false)
    {
        // Treat Timestamp property before calling parent implementation to
        // avoid a PHP warning.
        if (!$rawValue and $property == 'Timestamp') {
            return new Zend_Date(parent::getProperty('Timestamp', true), Zend_Date::TIMESTAMP);
        }

        $value = parent::getProperty($property, $rawValue);
        if ($rawValue) {
            return $value;
        }

        switch ($property) {
            case 'Platform':
                $map = array(
                    'WINDOWS' => 'windows',
                    'LINUX' => 'linux',
                    'MacOSX' => 'mac',
                );
                $value = $map[$value];
                break;
            case 'WarnMessage':
            case 'WarnCountdown':
            case 'WarnAllowAbort':
            case 'WarnAllowDelay':
                if (!$this->getWarn()) {
                    $value = null;
                }
                break;
            case 'PostInstMessage':
                if (!$this->getUserActionRequired()) {
                    $value = null;
                }
                break;
        }
        return $value;
    }

    /**
     * Set a property by its logical name
     *
     * Mangles platform names from abstract values to raw database values.
     * Raw values are accepted as well.
     */
    public function setProperty($property, $value)
    {
        if ($property == 'Platform') {
            $map = array(
                'windows' => 'WINDOWS',
                'linux' => 'LINUX',
                'mac' => 'MacOSX',
                'WINDOWS' => 'WINDOWS',
                'LINUX' => 'LINUX',
                'MacOSX' => 'MacOSX',
            );
            $value = $map[$value];
        } elseif ($property == 'Timestamp' and $value instanceof \Zend_Date) {
            // Raw value is UNIX timestamp, prevent parent from storing as ISO8601 string.
            $value = $value->get(\Zend_Date::TIMESTAMP);
        }
        parent::setProperty($property, $value);
    }

    /**
     * Return all packages matching criteria, including deployment statistics
     *
     * @param string $order Logical property to sort by. Default: null
     * @param string $direction one of [asc|desc].
     * @param array $conditions additional WHERE conditions
     * @param array $args arguments which replace placeholders in $conditions
     * @return \Model_Package[]
     */
    public function fetchAll($order=null, $direction='asc', $conditions=array(), $args=array())
    {
        $db = Model_Database::getAdapter();

        $subqueryNonnotified = $db->select()
            ->from('devices', 'COUNT(devices.hardware_id)')
            ->joinLeft('hardware', 'devices.hardware_id=hardware.id', array())
            ->where("devices.name='DOWNLOAD'")
            ->where('devices.ivalue=download_enable.id')
            ->where('devices.tvalue IS NULL')
            ->where("hardware.deviceid != '_SYSTEMGROUP_'");
        $subquerySuccess = $db->select()
            ->from('devices', 'COUNT(hardware_id)')
            ->where("name='DOWNLOAD'")
            ->where('ivalue=download_enable.id')
            ->where("tvalue='SUCCESS'");
        $subqueryNotified = $db->select()
            ->from('devices', 'COUNT(hardware_id)')
            ->where("name='DOWNLOAD'")
            ->where('ivalue=download_enable.id')
            ->where("tvalue='NOTIFIED'");
        $subqueryError = $db->select()
            ->from('devices', 'COUNT(hardware_id)')
            ->where("name='DOWNLOAD'")
            ->where('ivalue=download_enable.id')
            ->where("tvalue LIKE 'ERR%'");

        $select = $db->select()
            ->from(
                'download_available', array(
                    'fileid',
                    'name',
                    'priority',
                    'fragments',
                    'size',
                    'osname',
                    'comment',
                )
            )
            ->joinLeftUsing(
                'download_enable', 'fileid', array(
                    'id',
                    'num_nonnotified' => new \Zend_Db_Expr("($subqueryNonnotified)"),
                    'num_success' => new \Zend_Db_Expr("($subquerySuccess)"),
                    'num_notified' => new \Zend_Db_Expr("($subqueryNotified)"),
                    'num_error' => new \Zend_Db_Expr("($subqueryError)"),
                )
            )
            ->order(self::getOrder($order, $direction, $this->_propertyMap));

        foreach ($conditions as $condition) {
            $select->where($condition);
        }

        return $this->_fetchAll($select->query(null, $args));
    }

    /**
     * Get all package names
     *
     * @return string[]
     */
    public function getAllNames()
    {
        return \Library\Application::getService('Database\Table\Packages')->fetchCol('name');
    }

    /**
     * Populate object with values from an array
     *
     * Unknown keys are ignored. The data is not validated!
     * @param array $data properties as keys
     */
    public function fromArray($data)
    {
        foreach ($data as $property => $value) {
            if (array_key_exists($property, $this->_propertyMap)) {
                $this->setProperty($property, $value);
            }
        }
    }

    /**
     * Populate object with values from an existing package
     *
     * Only metadata is copied, not the downloadable content. As a consequence,
     * content-related metadata (like the hash value) isn't copied neither.
     *
     * Since MaxFragmentSize is not stored anywhere, it has to be estimated
     * from Size and NumFragments. The result will almost certainly be different
     * from the original value, but still about the same magnitude. In particular,
     * it will deliver the same number of fragments.
     *
     * Check the return value: if this method fails, the instance may be in an
     * incostinstent state. Call {@link getErrors()} to retrieve details.
     * @param string $name Name of an existing package to be cloned.
     * @return bool TRUE if the action was successful, FALSE if an error occured.
     */
    public function fromName($name)
    {
        $this->_errors = array();
        $this->setName($name); // Set name unconditionally, even on subsequent errors

        // Clone properties from database
        $packages = $this->fetchAll(
            null,
            null,
            array('name=?'),
            array($name)
        );
        if (!$packages) {
            $this->_setError('There is no package with name \'%s\'.', $name);
            return false;
        }
        foreach ($packages[0] as $property => $value) {
            $this->setProperty($property, $value);
        }

        // Clone properties from metafile
        $storage = \Library\Application::getService('Model\Package\Storage\Direct');
        $metadata = $storage->readMetadata($this['Timestamp']);
        foreach ($metadata as $property => $value) {
            $this->setProperty($property, $value);
        }

        // Estimate MaxFragmentSize
        $num = $this->getNumFragments();
        if ($num <= 1) {
            $max = 0; // no fragmentation
        } else {
            $size = $this->getSize();
            // average (/2) in kilobytes (/1024)
            $max = ceil(
                (($size / $num) + ($size / ($num - 1))) / 2048
            );
        }
        $this->setMaxFragmentSize($max);

        return true;
    }

    /**
     * Get path to downloadable file
     * @return string
     * @deprecated Functionality moved to Storage class
     */
    public function getPath()
    {
        return \Zend\Filter\StaticFilter::execute(
            \Library\Application::getService('Model\Config')->packagePath
            . DIRECTORY_SEPARATOR
            . $this->getTimestamp()->get(Zend_Date::TIMESTAMP),
            'RealPath',
            array ('exists' => false)
        );
    }

    /**
     * Build and activate package (metafile, package files and database entries)
     *
     * The Timestamp property will be set automatically. All other relevant
     * properties must be set to valid data before calling this.
     *
     * Warning: The file supplied by the FileLocation property may or may not
     * be deleted during the process.
     *
     * After successful build, the download URIs are tested.
     *
     * Errors can be retrieved via {@link getErrors()}. The result may also contain
     * nonfatal errors (like a failed URI test) even if build() returns TRUE.
     * If an error occurs, any files and database entries created that far
     * are cleaned up.
     *
     * @param bool $deleteSource If this is TRUE, the source file will be deleted as soon as possible.
     * @return bool TRUE if package was built and activated successfully, FALSE on error.
     */
    public function build($deleteSource)
    {
        $result = $this->_build($deleteSource);
        $this->_cleanup();
        return $result;
    }

    /**
     * Called internally by build(), does all the work
     *
     * @param bool $deleteSource If this is TRUE, the source file will be deleted as soon as possible.
     * @return bool TRUE if package was built and activated successfully, FALSE on error.
     */
    protected function _build($deleteSource)
    {
        $config = \Library\Application::getService('Model\Config');
        $storage = \Library\Application::getService('Model\Package\Storage\Direct');
        $packageManager = \Library\Application::getService('Model\Package\PackageManager');

        // Reset object's state
        $this->_errors = array();
        $this->_needCleanup = false;

        // Check if the name already exists
        $db = Model_Database::getAdapter();
        if ($db->fetchRow(
            'SELECT name FROM download_available WHERE name=?',
            $this->getName()
        )) {
            $this->_setError('Package \'%s\' already exists.', $this->getName());
            return false;
        }

        // Set timestamp
        $timestamp = time();
        $this->setTimestamp($timestamp);

        // Create directory
        $path = $this->getPath();
        if (@stat($path)) {
            $this->_setError('Directory \'%s\' already exists.', $path);
            return false;
        }
        if (!@mkdir($path)) {
            $this->_setError('Directory \'%s\' could not be created.', $path);
            return false;
        }
        $this->_directoryCreated = true;
        $this->_needCleanup = true; // From now on, package is dirty until it's finished.

        // Wrap file into ZIP archive if necessary
        if ($this->getFileLocation()
            and $this->getPlatform() == 'windows'
            and $this->getFileType() != 'application/zip'
        ) {
            $zipFileCreated = true;
            $file = $path . DIRECTORY_SEPARATOR . 'tmp.zip';
            $zip = new ZipArchive;
            $result = $zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::EXCL);
            if ($result === true) {
                $result = $zip->addFile(
                    $this->getFileLocation(),
                    $this->getFileName()
                );
                if ($result === true) {
                   $result = $zip->close();
                }
            }
            if ($result !== true) {
                $this->_setError(
                    'ZIP archive could not be created. Error code: %d',
                    $result
                );
                return false;
            }
            if ($deleteSource and !@unlink($this->getFileLocation())) {
                $this->_setError(
                    'Could not delete source file \'%s\'',
                    $this->getFileLocation()
                );
                return false;
            }
        } else {
            $zipFileCreated = false;
            $file = $this->getFileLocation();
        }

        // Compute SHA1 hash.
        if ($this->getFileLocation()) {
            $hash = sha1_file($file);
            if (!$hash) {
                $this->_setError(
                    'Could not compute SHA1 hash of file \'%s\'.',
                    $file
                );
                return false;
            }
        } else {
            $hash = null;
        }
        $this->setHash($hash);

        // Determine package size.
        if ($this->getFileLocation()) {
            $fileSize = @filesize($file);
            if (!$fileSize) {
                $this->_setError('Could not determine size of file \'%s\'.', $file);
            }
        } else {
            $fileSize = 0;
        }
        $this->setSize($fileSize);

        // Determine number of fragments and split file if necessary
        $baseName = $path . DIRECTORY_SEPARATOR . $timestamp . '-';
        if (!$this->getFileLocation()) {
            $this->setNumFragments(0);
        } elseif ($fileSize == 0 or $this->getMaxFragmentSize() == 0) {
            // Don't split, just move/rename the file
            $this->setNumFragments(1);
            if (!@rename($file, $baseName . '1')) {
                $this->_setError(
                    'Could not move/rename file \'%1$s\' to \'%2$s\'',
                    array(
                        $file,
                        $baseName . '1',
                    )
                );
                return false;
            }
        } else {
            // Split file into fragments of nearly identical size.
            $numFragments = ceil(
                $fileSize / ($this->getMaxFragmentSize() * 1024)
            );
            $fragmentSize = ceil($fileSize / $numFragments);
            // Determine number of fragments by files actually written
            // to avoid bad sideeffects of rounding error
            $numFragments = 0;
            $input = @fopen($file, 'rb');
            if (!$input) {
                $this->_setError('Could not open file \'%s\' for reading.', $file);
                return false;
            }
            $bytesRead = 0;
            while ($chunk = @fread($input, $fragmentSize)) {
                $numFragments++;
                $bytesRead += strlen($chunk);
                $outputName = $baseName . $numFragments;
                if (!file_put_contents($outputName, $chunk)) {
                    fclose($input);
                    $this->_setError(
                        'Could not write to file \'%s\'.',
                        $outputName
                    );
                    return false;
                }
            }
            fclose($input);

            // Delete source file
            if (($deleteSource or $zipFileCreated) and !@unlink($file)) {
                $this->_setError('Could not delete source file \'%s\'', $file);
                return false;
            }

            // Check whether all bytes have been read.
            if ($bytesRead != $fileSize) {
                $this->_setError(
                    'Reading from file \'%s\' was incomplete.',
                    $file
                );
                return false;
            }
            $this->setNumFragments($numFragments);
        }

        try {
            $storage->writeMetadata($this);
            $packageManager->build($this);
            $this->_writtenToDb = true;
            $this->_activated = true;

            // portable replacement for lastInsertId()
            $this->setEnabledId(
                $db->fetchOne(
                    'SELECT id FROM download_enable WHERE fileid=?',
                    $this->getTimestamp()->get(Zend_Date::TIMESTAMP)
                )
            );
        } catch (\Exception $e) {
            $this->_errors[] = $e->getMessage();
            return false;
        }

        $this->_needCleanup = false; // Package finished. Do not clean up.

        return true;
    }

    /**
     * Remove all database entries and filesystem objects related to this package
     *
     * Any errors encountered will be availabe via {@link getErrors()}.
     * @return bool TRUE if everything was deleted, FALSE if an error occurred.
     */
    public function delete()
    {
        $this->_errors = array();

        if (!$this->getTimestamp()) {
            return false;
        }

        // Unaffect package from all computers and groups
        Model_Database::getAdapter()->delete(
            'devices',
            array(
                "name LIKE 'DOWNLOAD%' AND ivalue IN"
                . '(SELECT id FROM download_enable WHERE fileid=?)'
                => $this->getTimestamp()->get(Zend_Date::TIMESTAMP)
            )
        );

        // Mark package "dirty" and let cleanup() do the rest
        $this->_directoryCreated = true;
        $this->_writtenToDb = true;
        $this->_activated = true;
        $this->_needCleanup = true;

        $result = $this->_cleanup();

        return $result;
    }

    /**
     * Deploy package to all computers which had a previous package assigned
     * and deployment status in a given state
     *
     * @param Model_Package $oldPackage package to be replaced
     * @param bool $deployNonnotified Deploy to computers with status 'not notified'
     * @param bool $deploySuccess Deploy to computers with status 'success'
     * @param bool $deployNotified Deploy to computers with status 'notified'
     * @param bool $deployError Deploy to computers with status 'error'
     * @param bool $deployGroups Deploy to groups
     */
    public function updateComputers(
        Model_Package $oldPackage,
        $deployNonnotified,
        $deploySuccess,
        $deployNotified,
        $deployError,
        $deployGroups
    )
    {
        if (!($deployNonnotified or $deploySuccess or $deployNotified or $deployError or $deployGroups)) {
            return; // nothing to do
        }

        $where = array('ivalue = ?' => $oldPackage->getEnabledId());

        // Additional filters are only necessary if not all conditions are set
        if (!($deployNonnotified and $deploySuccess and $deployNotified and $deployError and $deployGroups)) {
            if ($deployNonnotified) {
                $whereOr[] = '(tvalue IS NULL AND hardware_id NOT IN (SELECT hardware_id FROM groups))';
            }
            if ($deploySuccess) {
                $whereOr[] = 'tvalue=\'SUCCESS\'';
            }
            if ($deployNotified) {
                $whereOr[] = 'tvalue=\'NOTIFIED\'';
            }
            if ($deployError) {
                $whereOr[] = 'tvalue LIKE \'ERR%\'';
            }
            if ($deployGroups) {
                $whereOr[] = 'hardware_id IN (SELECT hardware_id FROM groups)';
            }
            $where['(' . implode(' OR ', $whereOr) . ')'] = null;
        }

        $db = Model_Database::getAdapter();
        // Remove DOWNLOAD_FORCE option - not necessary for a new package
        $db->delete('devices', array('name = ?' => 'DOWNLOAD_FORCE') + $where);
        // Update package ID for other download options
        $db->update(
            'devices',
            array('ivalue' => $this->getEnabledId()),
            array('name != ?' => 'DOWNLOAD_SWITCH', 'name LIKE ?' => 'DOWNLOAD_%') + $where
        );
        // Update package ID and reset download entry
        $db->update(
            'devices',
            array(
                'ivalue' => $this->getEnabledId(),
                'tvalue' => null, // always set new package status to 'not notified'
                'comments' => $this->getLocaltimeCompat(),
            ),
            array('name=?' => 'DOWNLOAD') + $where
        );
    }


    /**
     * Clean up any files, directories and database entries that were created
     * for an unfinished package
     * @return bool TRUE if everything was cleaned up, FALSE if filesystem objects could not be deleted.
     */
    protected function _cleanup()
    {
        if (!$this->_needCleanup)
            return true;

        $success = true; // set to false on error
        if ($this->_directoryCreated) {
            $path = $this->getPath();
            $files = @scandir($path);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file != '.' and $file != '..') {
                        // We don't create subdirectories, so we can just call unlink().
                        // Errors can be ignored since they will be caught in the next step.
                        @unlink($path . DIRECTORY_SEPARATOR . $file);
                    }
                }
                if (!@rmdir($path)) {
                    $this->_setError(
                        'Directory \'%s\' could not be deleted.',
                        $path
                    );
                    $success = false;
                }
            } else {
                $this->_setError(
                    'Directory \'%s\' does not exist.',
                    $path
                );
                // not fatal - we just can't clean up what is not there.
            }
            $this->_directoryCreated = false;
        }

        if ($this->_activated) {
            Model_Database::getAdapter()->delete(
                'download_enable',
                array("fileid=?" => $this->getTimestamp()->get(Zend_Date::TIMESTAMP))
            );
            $this->_activated = false;
        }
        if ($this->_writtenToDb) {
            Model_Database::getAdapter()->delete(
                'download_available',
                array("fileid=?" => $this->getTimestamp()->get(Zend_Date::TIMESTAMP))
            );
            $this->_writtenToDb = false;
        }

        return $success;
    }

    /**
     * Append error message to the list.
     *
     * @param string $message untranslated message with optional {@link sprintf()
     * sprintf()}-style placeholders
     * @param mixed $args single argument or array of arguments for placeholders
     */
    protected function _setError($message, $args)
    {
        if (!is_array($args)) {
            $args = array($args);
        }
        $this->_errors[] = array($message => $args);
    }

    /**
     * Get all error messages issued by the last operation
     *
     * @return array[] Array of associative arrays (template => arg[])
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Get number of error messages issued by the last operation
     * @return integer
     */
    public function numErrors()
    {
        return count($this->_errors);
    }

    /**
     * Get a timestamp in the format used in the 'devices' table
     *
     * The communication server uses perl's localtime() function to store
     * timestamps as a string in the 'devices' table. The format is almost
     * identical to strftime('%a %b %e %T %Y'), but not does not use a locale,
     * i.e. the month names are always abbreviated in english.
     *
     * This method provides a string in the same non-localized format.
     * It should only be used for this special purpose.
     *
     * @return string Current timestamp in the special format
     */
    static function getLocaltimeCompat()
    {
        $weekdays = array(
            'Sun',
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
        );
        $months = array(
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        );
        $date = new Zend_Date;
        $weekday = $weekdays[$date->get(Zend_Date::WEEKDAY_DIGIT)];
        $month = $months[$date->get(Zend_Date::MONTH_SHORT) - 1];
        $day = $date->get(Zend_Date::DAY_SHORT);
        $hour = $date->get(Zend_Date::HOUR);
        $minute = $date->get(Zend_Date::MINUTE);
        $second = $date->get(Zend_Date::SECOND);
        $year = $date->get(Zend_Date::YEAR);

        if ($day < 10) {
            $day = ' ' . $day;
        }

        return "$weekday $month $day $hour:$minute:$second $year";
    }

}

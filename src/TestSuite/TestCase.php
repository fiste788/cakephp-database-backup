<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 * @since       2.0.0
 */
namespace DatabaseBackup\TestSuite;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase as CakeTestCase;
use Reflection\ReflectionTrait;

/**
 * TestCase class
 */
abstract class TestCase extends CakeTestCase
{
    use ReflectionTrait;

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->deleteAllBackups();
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     * @uses deleteAllBackups()
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->deleteAllBackups();

        Configure::write(DATABASE_BACKUP . '.connection', 'test');
    }

    /**
     * Internal method to create a backup file
     * @return string
     */
    protected function createBackup()
    {
        return $this->BackupExport->filename('backup.sql')->export();
    }

    /**
     * Internal method to creates some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     */
    protected function createSomeBackups($sleep = false)
    {
        $files[] = $this->BackupExport->filename('backup.sql')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.bz2')->export();

        if ($sleep) {
            sleep(1);
        }

        $files[] = $this->BackupExport->filename('backup.sql.gz')->export();

        return $files;
    }

    /**
     * Deletes all backups
     * @return void
     */
    public function deleteAllBackups()
    {
        foreach (glob(Configure::read(DATABASE_BACKUP . '.target') . DS . '*') as $file) {
            //@codingStandardsIgnoreLine
            @unlink($file);
        }
    }

    /**
     * Loads all fixtures declared in the `$fixtures` property
     * @return void
     */
    public function loadAllFixtures()
    {
        $fixtures = $this->getProperty($this->fixtureManager, '_fixtureMap');

        call_user_func_array([$this, 'loadFixtures'], array_keys($fixtures));
    }
}

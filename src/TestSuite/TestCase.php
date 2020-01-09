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
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase as BaseTestCase;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Utility\BackupExport;
use Tools\ReflectionTrait;
use Tools\TestSuite\TestTrait;

/**
 * TestCase class
 */
abstract class TestCase extends BaseTestCase
{
    use BackupTrait;
    use ReflectionTrait;
    use TestTrait;

    /**
     * `BackupManager` instance
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        if (method_exists($this, 'loadPlugin')) {
            $this->loadPlugins(Configure::read('pluginsToLoad') ?: ['MeTools']);
        }

        $this->BackupExport = $this->BackupExport ?: new BackupExport();
    }

    /**
     * Called after every test method
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        try {
            if (LOGS !== TMP) {
                unlink_recursive(LOGS, 'empty');
            }
        } catch (Exception $e) {
            //Ignores exceptions
        }

        //Deletes all backup files
        unlink_recursive(Configure::read('DatabaseBackup.target'));

        parent::tearDown();
    }

    /**
     * Internal method to create a backup file
     * @param string $filename Filename
     * @return string
     */
    protected function createBackup($filename = 'backup.sql')
    {
        return $this->BackupExport->filename($filename)->export();
    }

    /**
     * Internal method to creates some backup files
     * @param bool $sleep If `true`, waits a second for each backup
     * @return array
     * @uses createBackup()
     */
    protected function createSomeBackups($sleep = false)
    {
        $files[] = $this->createBackup();

        $sleep ? sleep(1) : null;
        $files[] = $this->createBackup('backup.sql.bz2');

        $sleep ? sleep(1) : null;
        $files[] = $this->createBackup('backup.sql.gz');

        return $files;
    }

    /**
     * Internal method to mock a driver
     * @param string $className Driver class name
     * @param array $methods The list of methods to mock
     * @return \DatabaseBackup\Driver\Driver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockForDriver($className, array $methods)
    {
        return $this->getMockBuilder($className)
            ->setMethods($methods)
            ->setConstructorArgs([$this->getConnection('test')])
            ->getMock();
    }

    /**
     * Get a table instance from the registry
     * @param string $alias The alias name you want to get
     * @param array $options The options you want to build the table with
     * @return \Cake\ORM\Table|null
     * @since 2.18.11
     */
    protected function getTable($alias, array $options = [])
    {
        if ($alias === 'App' || (isset($options['className']) && !class_exists($options['className']))) {
            return null;
        }

        TableRegistry::getTableLocator()->clear();

        return TableRegistry::getTableLocator()->get($alias, $options);
    }

    /**
     * Loads all fixtures declared in the `$fixtures` property
     * @return void
     */
    public function loadFixtures()
    {
        $fixtures = $this->getProperty($this->fixtureManager, '_fixtureMap');

        foreach (array_keys($fixtures) as $fixture) {
            parent::loadFixtures($fixture);
        }
    }
}

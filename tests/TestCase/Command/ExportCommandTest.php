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
 */
namespace DatabaseBackup\Test\TestCase\Command;

use Cake\Core\Configure;
use DatabaseBackup\TestSuite\ConsoleIntegrationTestTrait;
use DatabaseBackup\TestSuite\TestCase;

/**
 * ExportCommandTest class
 */
class ExportCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * @var string
     */
    protected static $command = 'database_backup.export -v';

    /**
     * Test for `execute()` method
     * @test
     */
    public function testExecute()
    {
        $targetRegex = preg_quote(Configure::read('DatabaseBackup.target') . DS, '/');

        //Exports, without params
        $this->exec(self::$command);
        $this->assertExitWithSuccess();
        $this->assertOutputContains('Connection: test');
        $this->assertOutputContains('Driver: Mysql');
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup_test_\d+\.sql` has been exported/');

        //Exports, with `compression` param
        sleep(1);
        $this->exec(self::$command . ' --compression bzip2');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup_test_\d+\.sql\.bz2` has been exported/');

        //Exports, with `filename` param
        sleep(1);
        $this->exec(self::$command . ' --filename backup.sql');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup.sql` has been exported/');

        //Exports, with `rotate` param
        sleep(1);
        $this->exec(self::$command . ' --rotate 3 -v');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup_test_\d+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `backup_test_\d+\.sql` has been deleted/');
        $this->assertOutputContains('<success>Deleted backup files: 1</success>');

        //Exports, with `send` param
        sleep(1);
        $this->exec(self::$command . ' --send mymail@example.com');
        $this->assertExitWithSuccess();
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup_test_\d+\.sql` has been exported/');
        $this->assertOutputRegExp('/Backup `' . $targetRegex . 'backup_test_\d+\.sql` was sent via mail/');
    }

    /**
     * Test for `execute()` method, with an invalid option value
     * @test
     */
    public function testExecuteInvalidOptionValue()
    {
        $this->exec(self::$command . ' --filename /noExistingDir/backup.sql');
        $this->assertExitWithError();
    }
}
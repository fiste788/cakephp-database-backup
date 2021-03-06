<?php
declare(strict_types=1);
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
namespace DatabaseBackup\Test\TestCase\Utility;

use Cake\Core\Configure;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\BackupExport;
use DatabaseBackup\Utility\BackupImport;
use InvalidArgumentException;
use Tools\Exception\NotReadableException;

/**
 * BackupImportTest class
 */
class BackupImportTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\BackupExport
     */
    protected $BackupExport;

    /**
     * @var \DatabaseBackup\Utility\$BackupImport
     */
    protected $BackupImport;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->BackupExport = new BackupExport();
        $this->BackupImport = new BackupImport();
    }

    /**
     * Test for `construct()` method
     * @test
     */
    public function testConstruct()
    {
        $this->assertInstanceof(Mysql::class, $this->getProperty($this->BackupImport, 'driver'));
        $this->assertNull($this->getProperty($this->BackupImport, 'filename'));
    }

    /**
     * Test for `filename()` method. This tests also `$compression` property
     * @test
     */
    public function testFilename()
    {
        //Creates a `sql` backup
        $backup = $this->BackupExport->filename('backup.sql')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.bz2` backup
        $backup = $this->BackupExport->filename('backup.sql.bz2')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //Creates a `sql.gz` backup
        $backup = $this->BackupExport->filename('backup.sql.gz')->export();
        $this->BackupImport->filename($backup);
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //With a relative path
        $this->BackupImport->filename(basename($backup));
        $this->assertEquals($backup, $this->getProperty($this->BackupImport, 'filename'));

        //With an invalid directory
        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('File or directory `' . $this->BackupExport->getAbsolutePath('noExistingDir' . DS . 'backup.sql') . '` is not readable');
        $this->BackupImport->filename('noExistingDir' . DS . 'backup.sql');

        //With invalid extension
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file extension');
        file_put_contents(Configure::read('DatabaseBackup.target') . DS . 'backup.txt', null);
        $this->BackupImport->filename('backup.txt');
    }

    /**
     * Test for `import()` method, without compression
     * @test
     */
    public function testImport()
    {
        //Exports and imports with no compression
        $backup = $this->BackupExport->compression(null)->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql$/', basename($filename));

        //Exports and imports with `bzip2` compression
        $backup = $this->BackupExport->compression('bzip2')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.bz2$/', basename($filename));

        //Exports and imports with `gzip` compression
        $backup = $this->BackupExport->compression('gzip')->export();
        $filename = $this->BackupImport->filename($backup)->import();
        $this->assertRegExp('/^backup_test_[0-9]{14}\.sql\.gz$/', basename($filename));

        //Without filename
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You must first set the filename');
        $this->BackupImport->import();
    }
}

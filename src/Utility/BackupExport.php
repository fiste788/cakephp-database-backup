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
 * @see         https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility
 */
namespace DatabaseBackup\Utility;

use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Utility to export databases
 */
class BackupExport
{
    use BackupTrait;

    /**
     * `BackupManager` instance
     * @var \DatabaseBackup\Utility\BackupManager
     */
    public $BackupManager;

    /**
     * Compression type
     * @var string|null
     */
    protected $compression = null;

    /**
     * Database configuration
     * @var array
     */
    protected $config;

    /**
     * Default extension
     * @var string
     */
    protected $defaultExtension = 'sql';

    /**
     * Driver containing all methods to export/import database backups
     *  according to the database engine
     * @since 2.0.0
     * @var object
     */
    public $driver;

    /**
     * Recipient of the email, if you want to send the backup via mail
     * @var string|null
     */
    protected $emailRecipient = null;

    /**
     * Filename extension
     * @var string
     */
    protected $extension;

    /**
     * Filename where to export the database
     * @var string
     */
    protected $filename;

    /**
     * Rotate limit. This is the number of backups you want to keep. So, it
     *  will delete all backups that are older.
     * @var int
     */
    protected $rotate = 0;

    /**
     * Construct
     * @uses $BackupManager
     * @uses $config
     * @uses $driver
     */
    public function __construct()
    {
        $connection = $this->getConnection();
        $this->BackupManager = new BackupManager();
        $this->config = $connection->config();
        $this->driver = $this->getDriver($connection);
    }

    /**
     * Sets the compression
     * @param string|null $compression Compression type name. Supported
     *  values are `bzip2` and `gzip`. Use `null` for no compression
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#compression
     * @throws \InvalidArgumentException
     * @uses getValidCompressions()
     * @uses $compression
     * @uses $defaultExtension
     * @uses $extension
     */
    public function compression(?string $compression)
    {
        $this->extension = $this->defaultExtension;

        if ($compression) {
            $this->extension = array_search($compression, $this->getValidCompressions());
            is_true_or_fail(
                $this->extension,
                __d('database_backup', 'Invalid compression type'),
                InvalidArgumentException::class
            );
        }
        $this->compression = $compression;

        return $this;
    }

    /**
     * Sets the filename.
     *
     * The compression type will be automatically setted by the filename.
     * @param string $filename Filename. It can be an absolute path and may
     *  contain patterns
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#filename
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Tools\Exception\NotWritableException
     * @uses compression()
     * @uses $config
     * @uses $filename
     */
    public function filename(string $filename)
    {
        //Replaces patterns
        $filename = str_replace(['{$DATABASE}', '{$DATETIME}', '{$HOSTNAME}', '{$TIMESTAMP}'], [
            pathinfo($this->config['database'], PATHINFO_FILENAME),
            date('YmdHis'),
            empty($this->config['host']) ? 'localhost' : $this->config['host'],
            time(),
        ], $filename);

        $filename = $this->getAbsolutePath($filename);
        is_writable_or_fail(dirname($filename));
        is_true_or_fail(!file_exists($filename), __d('database_backup', 'File `{0}` already exists', $filename));

        //Checks for extension
        is_true_or_fail(
            $this->getExtension($filename),
            __d('database_backup', 'Invalid file extension'),
            InvalidArgumentException::class
        );

        //Sets the compression
        $this->compression($this->getCompression($filename));

        $this->filename = $filename;

        return $this;
    }

    /**
     * Sets the number of backups you want to keep. So, it will delete all
     * backups that are older
     * @param int $rotate Number of backups you want to keep
     * @return $this
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#rotate
     * @uses $rotate
     */
    public function rotate(int $rotate)
    {
        $this->rotate = $rotate;

        return $this;
    }

    /**
     * Sets the recipient's email address to send the backup file via mail
     * @param string|null $recipient Recipient's email address or `null` to disable
     * @return $this
     * @since 1.1.0
     * @uses $emailRecipient
     */
    public function send(?string $recipient = null)
    {
        $this->emailRecipient = $recipient;

        return $this;
    }

    /**
     * Exports the database
     * @return string Filename path
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupExport-utility#export
     * @uses filename()
     * @uses $BackupManager;
     * @uses $defaultExtension
     * @uses $emailRecipient
     * @uses $filename
     * @uses $extension
     * @uses $rotate
     */
    public function export(): string
    {
        if (empty($this->filename)) {
            $this->extension = $this->extension ?: $this->defaultExtension;
            $this->filename(sprintf('backup_{$DATABASE}_{$DATETIME}.%s', $this->extension));
        }

        //This allows the filename to be set again with a next call of this method
        $filename = $this->filename;
        unset($this->filename);

        $this->driver->export($filename);
        (new Filesystem())->chmod($filename, Configure::read('DatabaseBackup.chmod'));

        $this->emailRecipient ? $this->BackupManager->send($filename, $this->emailRecipient) : null;
        $this->rotate ? $this->BackupManager->rotate($this->rotate) : null;

        return $filename;
    }
}

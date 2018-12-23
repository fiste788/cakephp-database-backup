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
 * @since       2.6.0
 */
namespace DatabaseBackup\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupManager;
use Exception;

/**
 * Rotates backups
 */
class RotateCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param ConsoleOptionParser $parser The parser to be defined
     * @return ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->setDescription(__d('database_backup', 'Rotates backups'));
        $parser->addArgument('keep', [
            'help' => __d('database_backup', 'Number of backups you want to keep. So, it will delete all backups that are older'),
            'required' => true,
        ]);

        return $parser;
    }

    /**
     * Rotates backups.
     *
     * You have to indicate the number of backups you want to keep. So, it will
     *  delete all backups that are older. By default, no backup will be deleted
     * @param Arguments $args The command arguments
     * @param ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#rotate
     * @uses DatabaseBackup\Utility\BackupManager::rotate()
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);

        try {
            //Gets deleted files
            $deleted = (new BackupManager)->rotate($args->getArgument('keep'));

            if (empty($deleted)) {
                $io->verbose(__d('database_backup', 'No backup has been deleted'));

                return null;
            }

            foreach ($deleted as $file) {
                $io->verbose(__d('database_backup', 'Backup `{0}` has been deleted', $file->filename));
            }

            $io->success(__d('database_backup', 'Deleted backup files: {0}', count($deleted)));
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }
    }
}
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
use DatabaseBackup\Command\RotateCommand;
use DatabaseBackup\Command\SendCommand;
use DatabaseBackup\Console\Command;
use DatabaseBackup\Utility\BackupExport;
use Exception;

/**
 * Exports a database backup
 */
class ExportCommand extends Command
{
    /**
     * Hook method for defining this command's option parser
     * @param ConsoleOptionParser $parser The parser to be defined
     * @return ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser)
    {
        $parser->setDescription(__d('database_backup', 'Exports a database backup'));
        $parser->addOptions([
            'compression' => [
                'choices' => $this->getValidCompressions(),
                'help' => __d('database_backup', 'Compression type. By default, no compression will be used'),
                'short' => 'c',
            ],
            'filename' => [
                'help' => __d('database_backup', 'Filename. It can be an absolute path and may contain ' .
                    'patterns. The compression type will be automatically setted'),
                'short' => 'f',
            ],
            'rotate' => [
                'help' => __d('database_backup', 'Rotates backups. You have to indicate the number of backups you ' .
                    'want to keep. So, it will delete all backups that are older. By default, no backup will be deleted'),
                'short' => 'r',
            ],
            'send' => [
                'help' => __d('database_backup', 'Sends the backup file via email. You have ' .
                    'to indicate the recipient\'s email address'),
                'short' => 's',
            ],
        ]);

        return $parser;
    }

    /**
     * Exports a database backup.
     *
     * This command uses `RotateCommand` and `SendCommand`.
     * @param Arguments $args The command arguments
     * @param ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     * @see https://github.com/mirko-pagliai/cakephp-database-backup/wiki/How-to-use-the-BackupShell#export
     * @uses DatabaseBackup\Command\RotateCommand::execute()
     * @uses DatabaseBackup\Command\SendCommand::execute()
     * @uses DatabaseBackup\Utility\BackupExport::compression()
     * @uses DatabaseBackup\Utility\BackupExport::export()
     * @uses DatabaseBackup\Utility\BackupExport::filename()
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);

        try {
            $instance = new BackupExport;
            //Sets the output filename or the compression type.
            //Regarding the `rotate` option, the `BackupShell::rotate()` method
            //  will be called at the end, instead of `BackupExport::rotate()`
            if ($args->hasOption('filename')) {
                $instance->filename($args->getOption('filename'));
            } elseif ($args->hasOption('compression')) {
                $instance->compression($args->getOption('compression'));
            }

            //Exports
            $file = $instance->export();
            $io->success(__d('database_backup', 'Backup `{0}` has been exported', rtr($file)));

            //Sends via email
            if ($args->hasOption('send')) {
                $SendCommand = new SendCommand;
                $sendArgs = new Arguments(
                    [$file, $args->getOption('send')],
                    ['verbose' => $args->getOption('verbose'), 'quiet' => $args->getOption('quiet')],
                    $SendCommand->getOptionParser()->argumentNames()
                );
                $SendCommand->execute($sendArgs, $io);
            }

            //Rotates
            if ($args->hasOption('rotate')) {
                $RotateCommand = new RotateCommand;
                $rotateArgs = new Arguments(
                    [$args->getOption('rotate')],
                    ['verbose' => $args->getOption('verbose'), 'quiet' => $args->getOption('quiet')],
                    $RotateCommand->getOptionParser()->argumentNames()
                );
                $RotateCommand->execute($rotateArgs, $io);
            }
        } catch (Exception $e) {
            $io->error($e->getMessage());
            $this->abort();
        }

        return null;
    }
}
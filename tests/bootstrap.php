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
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\Routing\DispatcherFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Path constants to a few helpful things.
define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests');
define('APP', ROOT . 'tests' . DS . 'test_app' . DS);
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', APP . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', APP . 'config' . DS);
define('CACHE', TMP);
define('LOGS', TMP);
define('SESSIONS', TMP . 'sessions' . DS);

//@codingStandardsIgnoreStart
@mkdir(LOGS);
@mkdir(SESSIONS);
@mkdir(CACHE);
@mkdir(CACHE . 'views');
@mkdir(CACHE . 'models');
//@codingStandardsIgnoreEnd

require CORE_PATH . 'config' . DS . 'bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'App',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => APP_DIR,
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [APP . 'Plugin' . DS],
        'templates' => [APP . 'TestApp' . DS . 'Template' . DS],
    ]
]);

Cache::config([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
    '_cake_model_' => [
        'engine' => 'File',
        'prefix' => 'cake_model_',
        'serialize' => true,
    ],
    'default' => [
        'engine' => 'File',
        'prefix' => 'default_',
        'serialize' => true,
    ],
]);

if (version_compare(Configure::version(), '3.4.13', '>=')) {
    $sqliteUrl = 'sqlite:///' . TMP . 'example.sq3';
} else {
    $sqliteUrl = 'sqlite:\\' . TMP . 'example.sq3';
}

if (!getenv('db_dsn')) {
    putenv('db_dsn=mysql://root:@localhost/test');
}
if (!getenv('db_dsn_postgres')) {
    putenv('db_dsn_postgres=postgres://postgres@localhost/travis_ci_test');
}

ConnectionManager::config('test', ['url' => getenv('db_dsn')]);
ConnectionManager::config('test_postgres', ['url' => getenv('db_dsn_postgres')]);
ConnectionManager::config('test_sqlite', ['url' => $sqliteUrl]);

Configure::write('DatabaseBackup.connection', 'test');
Configure::write('DatabaseBackup.target', TMP . 'backups');
Configure::write('DatabaseBackup.mailSender', 'sender@example.com');

Plugin::load('DatabaseBackup', ['bootstrap' => true, 'path' => ROOT]);

//Sets debug log
Log::setConfig('debug', [
    'className' => 'File',
    'path' => LOGS,
    'levels' => ['notice', 'info', 'debug'],
    'file' => 'debug',
]);

DispatcherFactory::add('Routing');
DispatcherFactory::add('ControllerFactory');

Email::configTransport('debug', ['className' => 'Debug']);
Email::config('default', ['transport' => 'debug', 'log' => true]);

ini_set('intl.default_locale', 'en_US');

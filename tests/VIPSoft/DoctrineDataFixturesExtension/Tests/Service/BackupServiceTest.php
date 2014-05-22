<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Tests\Service;

use VIPSoft\DoctrineDataFixturesExtension\Service\BackupService;

/**
 * @group Service
 */
class BackupServiceTest extends \PHPUnit_Framework_TestCase
{
    private $service;
    private $mysqlBackup;
    private $sqliteBackup;
    private $mysqlPlatform;
    private $sqlitePlatform;
    private $mysqlConnection;
    private $sqliteConnection;

    public function setUp()
    {
        $this->mysqlPlatform    = $this->getMock('Doctrine\DBAL\Platforms\MySqlPlatform', array('getName'));
        $this->sqlitePlatform   = $this->getMock('Doctrine\DBAL\Platforms\SqlitePlatform',array('getName'));
        $this->mysqlBackup      = $this->getMock('VIPSoft\DoctrineDataFixturesExtension\Service\Backup\BackupInterface');
        $this->sqliteBackup     = $this->getMock('VIPSoft\DoctrineDataFixturesExtension\Service\Backup\BackupInterface');
        $this->mysqlConnection  = $this->getMock('Doctrine\DBAL\Connection', array('getDatabasePlatform', 'getDatabase', 'getParams'), array(), '', false);
        $this->sqliteConnection = $this->getMock('Doctrine\DBAL\Connection', array('getDatabasePlatform', 'getDatabase', 'getParams'), array(), '', false);
        $this->service          = new BackupService();

        $this->service->setCacheDir('/tmp/app/cache/test');
        $this->service->setPlatformBackupMap(array(
            'mysql'  => $this->mysqlBackup,
            'sqlite' => $this->sqliteBackup,
        ));

        $this->mysqlPlatform->expects($this->any())
             ->method('getName')
             ->will($this->returnValue('mysql'));

        $this->sqlitePlatform->expects($this->any())
             ->method('getName')
             ->will($this->returnValue('sqlite'));

        $this->mysqlConnection->expects($this->any())
             ->method('getDatabasePlatform')
             ->will($this->returnValue($this->mysqlPlatform));

        $this->sqliteConnection->expects($this->any())
             ->method('getDatabasePlatform')
             ->will($this->returnValue($this->sqlitePlatform));

        $this->mysqlConnection->expects($this->any())
             ->method('getDatabase')
             ->will($this->returnValue('mysql_db_name'));

        $this->sqliteConnection->expects($this->any())
             ->method('getDatabase')
             ->will($this->returnValue('sqlite_db_name'));

        $this->mysqlConnection->expects($this->any())
             ->method('getParams')
             ->will($this->returnValue(array(
                'host' => 'localhost',
                'user' => 'root',
                'pass' => 'root',
            )));

        $this->sqliteConnection->expects($this->any())
             ->method('getParams')
             ->will($this->returnValue(array(
                'path' => '/tmp/path/to/sqlite.db'
            )));
    }

    public function testRestoreBackup()
    {
        $this->mysqlBackup->expects($this->once())
             ->method('restore')
             ->with($this->equalTo('mysql_db_name'), $this->equalTo('/tmp/app/cache/test/test_hash1'),$this->equalTo(array(
                'host' => 'localhost',
                'user' => 'root',
                'pass' => 'root',
            )));

        $this->sqliteBackup->expects($this->once())
             ->method('restore')
             ->with($this->equalTo('sqlite_db_name'), $this->equalTo('/tmp/app/cache/test/test_hash2'),$this->equalTo(array(
                'path' => '/tmp/path/to/sqlite.db'
            )));

        $this->service->restoreBackup($this->mysqlConnection, 'hash1');
        $this->service->restoreBackup($this->sqliteConnection, 'hash2');
    }

    public function testCreateBackup()
    {
        $this->mysqlBackup->expects($this->once())
             ->method('create')
             ->with($this->equalTo('mysql_db_name'), $this->equalTo('/tmp/app/cache/test/test_hash1'),$this->equalTo(array(
                'host' => 'localhost',
                'user' => 'root',
                'pass' => 'root',
            )));

        $this->sqliteBackup->expects($this->once())
             ->method('create')
             ->with($this->equalTo('sqlite_db_name'), $this->equalTo('/tmp/app/cache/test/test_hash2'),$this->equalTo(array(
                'path' => '/tmp/path/to/sqlite.db'
            )));

        $this->service->createBackup($this->mysqlConnection, 'hash1');
        $this->service->createBackup($this->sqliteConnection, 'hash2');
    }
}

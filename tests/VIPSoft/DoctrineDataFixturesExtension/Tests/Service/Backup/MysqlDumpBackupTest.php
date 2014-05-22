<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Tests\Service;

use VIPSoft\DoctrineDataFixturesExtension\Service\Backup\MysqlDumpBackup;

/**
 * @group Service
 */
class MysqlDumpBackupTest extends \PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $this->service = new MysqlDumpBackupMockTest();
    }

    public function testCreateBackup()
    {
        $this->service->create('test_db', '/tmp/fileatime', array());

        $this->assertCount(1, $this->service->runCommandCalls);
        $this->assertEquals("mysqldump 'test_db' > '/tmp/fileatime'", $this->service->runCommandCalls[0]);
    }

    public function testRestoreBackup()
    {
        $this->service->restore('test_db', '/tmp/fileatime', array());

        $this->assertCount(1, $this->service->runCommandCalls);
        $this->assertEquals("mysql 'test_db' < '/tmp/fileatime'", $this->service->runCommandCalls[0]);
    }
}

class MysqlDumpBackupMockTest extends MysqlDumpBackup
{
    public $runCommandCalls = array();

    public function runCommand($command)
    {
        $this->runCommandCalls[] = $command;
    }
}
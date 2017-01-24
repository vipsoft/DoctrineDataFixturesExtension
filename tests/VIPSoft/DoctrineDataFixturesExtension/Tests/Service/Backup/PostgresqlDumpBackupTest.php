<?php

namespace VIPSoft\DoctrineDataFixturesExtension\Tests\Service\Backup;

use PHPUnit_Framework_TestCase;
use VIPSoft\DoctrineDataFixturesExtension\Service\Backup\PostgresqlDumpBackup;

/**
 * @group Service
 */
class PostgresqlDumpBackupTest extends PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $this->service = new PostgresqlDumpBackupMockTest();
    }

    public function testCreateBackup()
    {
        $this->service->create('test_db', '/tmp/fileatime', array());

        $this->assertCount(1, $this->service->runCommandCalls);
        $this->assertEquals("pg_dump -Fc  'test_db' > '/tmp/fileatime'", $this->service->runCommandCalls[0]);
    }

    public function testRestoreBackup()
    {
        $this->service->restore('test_db', '/tmp/fileatime', array());

        $this->assertCount(1, $this->service->runCommandCalls);
        $this->assertEquals("pg_restore --clean  --dbname='test_db' '/tmp/fileatime'", $this->service->runCommandCalls[0]);
    }
}

class PostgresqlDumpBackupMockTest extends PostgresqlDumpBackup
{
    public $runCommandCalls = array();

    public function runCommand($command)
    {
        $this->runCommandCalls[] = $command;
    }
}

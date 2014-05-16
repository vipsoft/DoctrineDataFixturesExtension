<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Tests\Service;

use VIPSoft\DoctrineDataFixturesExtension\Service\Backup\SqliteCopyBackup;

/**
 * @group Service
 */
class SqliteCopyBackupTest extends \PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $this->service = new SqliteCopyBackupMockTest();
    }

    public function testCreateBackup()
    {
        $this->service->create('test_db', '/tmp/backup-file', array(
            'path' => '/tmp/path/to/sqlite.db'
        ));

        $this->assertCount(1, $this->service->copyCalls);
        $this->assertEquals("/tmp/backup-file", $this->service->copyCalls[0]['dest']);
        $this->assertEquals("/tmp/path/to/sqlite.db", $this->service->copyCalls[0]['source']);
    }

    public function testRestoreBackup()
    {
        $this->service->restore('test_db', '/tmp/backup-file', array(
            'path' => '/tmp/path/to/sqlite.db'
        ));

        $this->assertCount(1, $this->service->copyCalls);
        $this->assertEquals("/tmp/backup-file", $this->service->copyCalls[0]['source']);
        $this->assertEquals("/tmp/path/to/sqlite.db", $this->service->copyCalls[0]['dest']);
    }
}

class SqliteCopyBackupMockTest extends SqliteCopyBackup
{
    public $copyCalls = array();

    public function copy($source, $dest)
    {
        $this->copyCalls[] = array(
            'source' => $source,
            'dest'   => $dest
        );
    }
}
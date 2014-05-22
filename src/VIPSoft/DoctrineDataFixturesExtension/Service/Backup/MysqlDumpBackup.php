<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service\Backup;

use Symfony\Component\Process\Process;

/**
 * Mysql dump backup
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class MysqlDumpBackup implements BackupInterface
{
    private $mysqldumpBin = 'mysqldump';
    private $mysqlBin     = 'mysql';

    /**
     * @param string $bin
     */
    public function setMysqldumpBin($bin)
    {
        $this->mysqldumpBin = $bin;
    }

    /**
     * @param string $bin
     */
    public function setMysqlBin($bin)
    {
        $this->mysqlBin = $bin;
    }

    /**
     * @param string $command
     *
     * @return integer
     *
     * @throws \RuntimeException
     */
    protected function runCommand($command)
    {
        $process = new Process($command);

        $process->run();

        if ( ! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getExitCode();
    }

    /**
     * {@inheritdoc}
     */
    public function create($database, $file, array $params)
    {
        $command = sprintf("%s %s > %s", $this->mysqldumpBin, escapeshellarg($database), escapeshellarg($file));

        if (isset($params['host'])) {
            $command .= sprintf(" -h%s", escapeshellarg($params['host']));
        }

        if (isset($params['user'])) {
            $command .= sprintf(" -u%s", escapeshellarg($params['user']));
        }

        if (isset($params['password'])) {
            $command .= sprintf(" -p%s", escapeshellarg($params['password']));
        }

        $this->runCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($database, $file, array $params)
    {
        $command = sprintf("%s %s < %s", $this->mysqlBin, escapeshellarg($database), escapeshellarg($file));

        if (isset($params['host'])) {
            $command .= sprintf(" -h%s", escapeshellarg($params['host']));
        }

        if (isset($params['user'])) {
            $command .= sprintf(" -u%s", escapeshellarg($params['user']));
        }

        if (isset($params['password'])) {
            $command .= sprintf(" -p%s", escapeshellarg($params['password']));
        }

        $this->runCommand($command);
    }
}

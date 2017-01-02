<?php

namespace VIPSoft\DoctrineDataFixturesExtension\Service\Backup;

use Symfony\Component\Process\Process;

class PostgresqlDumpBackup implements BackupInterface
{
    /**
     * @var string
     */
    private $pgRestore;

    /**
     * @var string
     */
    private $pgDump;

    /**
     * @param string $bin
     */
    public function setPgRestoreBin($bin)
    {
        $this->pgRestore = $bin;
    }

    /**
     * @param string $bin
     */
    public function setPgDumpBin($bin)
    {
        $this->pgDump = $bin;
    }

    /**
     * @param string $command
     *
     * @return int
     *
     * @throws \RuntimeException
     */
    protected function runCommand($command)
    {
        $process = new Process($command);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getExitCode();
    }

    /**
     * {@inheritdoc}
     */
    public function create($database, $file, array $params)
    {
        $options = '';

        if (isset($params['host']) && strlen($params['host'])) {
            $options .= sprintf(' --host=%s', escapeshellarg($params['host']));
        }

        if (isset($params['user']) && strlen($params['user'])) {
            $options .= sprintf(' --username=%s', escapeshellarg($params['user']));
        }

        if (isset($params['port'])) {
            $options .= sprintf(' --port=%s', escapeshellarg($params['port']));
        }

        $command = sprintf(
            '%s -Fc %s %s > %s',
            $this->pgDump,
            $options,
            escapeshellarg($database),
            escapeshellarg($file)
        );

        if (isset($params['password']) && strlen($params['password'])) {
            $command = sprintf('PGPASSWORD=%s ', escapeshellarg($params['password'])) . $command;
        }

        $this->runCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($database, $file, array $params)
    {
        $options = '';

        if (isset($params['host']) && strlen($params['host'])) {
            $options .= sprintf(' --host=%s', escapeshellarg($params['host']));
        }

        if (isset($params['user']) && strlen($params['user'])) {
            $options .= sprintf(' --username=%s', escapeshellarg($params['user']));
        }

        if (isset($params['port'])) {
            $options .= sprintf(' --port=%s', escapeshellarg($params['port']));
        }

        $command = sprintf(
            '%s --clean %s --dbname=%s %s',
            $this->pgRestore,
            $options,
            escapeshellarg($database),
            escapeshellarg($file)
        );

        if (isset($params['password']) && strlen($params['password'])) {
            $command = sprintf('PGPASSWORD=%s ', escapeshellarg($params['password'])) . $command;
        }

        $this->runCommand($command);
    }
}

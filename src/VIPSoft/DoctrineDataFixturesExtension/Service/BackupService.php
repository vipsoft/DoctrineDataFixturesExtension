<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use VIPSoft\DoctrineDataFixturesExtension\Service\Backup\BackupInterface;

/**
 * Data Backup Service
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class BackupService
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var string
     */
    private $platformBackupMap;

    /**
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param array $map
     */
    public function setPlatformBackupMap(array $map)
    {
        foreach ($map as $key => $value) {
            $this->setPlatformBackup($key, $value);
        };
    }

    /**
     * @return array
     */
    public function getPlatformBackupMap()
    {
        return $this->platformBackupMap;
    }

    /**
     * @param string                                                                $platformName
     * @param \VIPSoft\DoctrineDataFixturesExtension\Service\Backup\BackupInterface $backup
     */
    public function setPlatformBackup($platformName, BackupInterface $backup)
    {
        $this->platformBackupMap[$platformName] = $backup;
    }

    /**
     * @param string $name
     *
     * @return \VIPSoft\DoctrineDataFixturesExtension\Service\Backup\BackupInterface
     */
    public function getPlatformBackup($name)
    {
        $map  = $this->getPlatformBackupMap();
        $item = isset($map[$name]) ? $map[$name] : null;

        if ($item === null) {
            throw new \RuntimeException('Unsupported platform '. $name);
        }

        return $item;
    }

    /**
     * Returns absolute path to backup file
     *
     * @param string $hash
     *
     * @return string
     */
    public function getBackupFile($hash)
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR .'test_' . $hash;
    }

    /**
     * Check if there is a backup
     * @param string $hash
     *
     * @return boolean
     */
    public function hasBackup($hash)
    {
        return file_exists($this->getBackupFile($hash));
    }

    /**
     * Create a backup for the given connection / hash
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param string                    $hash
     */
    public function createBackup(Connection $connection, $hash)
    {
        $platform     = $connection->getDatabasePlatform();
        $filename     = $this->getBackupFile($hash);
        $database     = $connection->getDatabase();
        $params       = $connection->getParams();
        $platformName = $platform->getName();

        $this->getPlatformBackup($platformName)->create($database, $filename, $params);
    }

    /**
     * Restore the backup for the given connection / hash
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param string                    $hash
     */
    public function restoreBackup(Connection $connection, $hash)
    {
        $platform     = $connection->getDatabasePlatform();
        $filename     = $this->getBackupFile($hash);
        $database     = $connection->getDatabase();
        $params       = $connection->getParams();
        $platformName = $platform->getName();

        $this->getPlatformBackup($platformName)->restore($database, $filename, $params);
    }
}

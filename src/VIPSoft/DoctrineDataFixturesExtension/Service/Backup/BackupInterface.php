<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service\Backup;

/**
 * Backup platform interface
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface BackupInterface
{
    /**
     * Create a backup file for the given database
     *
     * @param string $database
     * @param string $file
     * @param array  $params
     */
    public function create($database, $file, array $params);

    /**
     * Restore the backup file into the given database
     *
     * @param string $database
     * @param string $file
     * @param array  $params
     */
    public function restore($database, $file, array $params);
}

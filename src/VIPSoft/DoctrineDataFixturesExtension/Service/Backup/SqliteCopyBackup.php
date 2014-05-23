<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service\Backup;

/**
 * Sqlite copy backup
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class SqliteCopyBackup implements BackupInterface
{
    /**
     * Get path to .db file
     *
     * @param array $params
     *
     * @return string
     */
    private function getDatabaseFile(array $params)
    {
        if ( ! isset($params['path'])) {
            throw new \RuntimeException("Invalid sqlite path config");
        }

        return $params['path'];
    }

    /**
     * Makes a copy of the file source to dest.
     *
     * @param string $source
     * @param string $dest
     */
    public function copy($source, $dest)
    {
        if ( ! copy($source, $dest)) {
            throw new \RuntimeException("Unable to copy '$source' to '$dest'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function create($database, $file, array $params)
    {
        $this->copy($this->getDatabaseFile($params), $file);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($database, $file, array $params)
    {
        $this->copy($file, $this->getDatabaseFile($params));
    }
}

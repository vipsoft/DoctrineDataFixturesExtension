<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Symfony\Component\HttpKernel\Kernel;

use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor,
    Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * Data Fixture Service
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class FixtureService
{
    private $fixture;
    private $kernel;
    private $entityManager;
    private $databaseFile;
    private $backupDbFile;

    /**
     * Constructor
     *
     * @param array  $fixture Fixture class names
     * @param Kernel $kernel  Application kernel
     */
    public function __construct($fixtures, Kernel $kernel)
    {
        $this->fixture = $fixtures;
        $this->kernel = $kernel;
    }

    /**
     * Lazy init
     */
    private function init()
    {
        $this->entityManager = $this->kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Calculate hash on data fixture class names
     *
     * @return string
     */
    private function generateHash($fixtures)
    {
        $classNames = array_map('get_class', $fixtures);

        sort($classNames);

        return sha1(serialize($classNames));
    }

    /**
     * Fetch bundle fixtures
     *
     * @return array Array of data fixture objects
     */
    private function fetchBundleFixtures()
    {
        $loader = new DataFixturesLoader($this->kernel->getContainer());

        foreach ($this->kernel->getBundles() as $bundle) {
            $path = $bundle->getPath() . '/DataFixtures/ORM';

            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        return $loader->getFixtures();
    }

    /**
     * Fetch fixtures
     *
     * @return array Array of data fixture objects
     */
    private function fetchFixtures()
    {
        $fixtures = array();

        foreach ($this->fixtures as $className) {
            if (substr($className, 0, 1) !== '\\') {
                $className = '\\' . $className;
            }

            $fixtures[] = new $className;
        }

        return $fixtures;
    }

    /**
     * Load fixtures into database
     */
    private function loadFixtures()
    {
        $em = $this->entityManager;

        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        $executor = new ORMExecutor($em, $purger);
        $executor->purge();
        $executor->execute($this->fixtures, true);
    }

    /**
     * Get path to .db file when using SqliteDriver
     *
     * @return string
     */
    private function getDatabaseFile()
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();

        if ($connection->getDriver() instanceof SqliteDriver) {
            $params = $connection->getParams();
        }

        return isset($params['path']) ? $params['path'] : null;
    }

    /**
     * Save data fixtures to backup file
     */
    private function backupFixtures()
    {
        $cacheDirectory = $this->kernel->getContainer()->getParameter('kernel.cache_dir');

        $this->backupDbFile = $cacheDirectory . '/test_' . $this->generateHash($this->fixtures) . '.db';

        copy($this->databaseFile, $this->backupDbFile);
    }

    /**
     * Restore fixtures from backup
     */
    private function restoreFixtures()
    {
        if (file_exists($this->backupDbFile)) {
            copy($this->backupDbFile, $this->databaseFile);

            return;
        }

        $this->loadFixtures();
        $this->backupFixtures();
    }

    /**
     * Cache data fixtures
     */
    public function cacheFixtures()
    {
        $this->init();

        $this->fixtures = isset($this->fixtures) ? $this->fetchFixtures() : $this->fetchBundleFixtures();

        $this->databaseFile = $this->getDatabaseFile();
    }

    /**
     * Reload data fixtures
     */
    public function reloadFixtures()
    {
        if (isset($this->databaseFile)) {
            $this->restoreFixtures();

            return;
        }

        $this->loadFixtures();
    }

    /**
     * Flush entity manager
     */
    public function flush()
    {
        $em = $this->entityManager;
        $em->flush();
        $em->clear();
    }
}

<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as SymfonyFixturesLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use VIPSoft\DoctrineDataFixturesExtension\EventListener\PlatformListener;

/**
 * Data Fixture Service
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class FixtureService
{
    private $loader;
    private $autoload;
    private $fixtures;
    private $directories;
    private $migrations;
    private $kernel;
    private $entityManager;
    private $listener;
    private $databaseFile;
    private $backupDbFile;
    private $useBackup;

    /**
     * @var \Doctrine\Common\DataFixtures\ReferenceRepository
     */
    private $referenceRepository;

    /**
     * Constructor
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container Service container
     * @param \Symfony\Component\HttpKernel\Kernel                      $kernel    Application kernel
     */
    public function __construct(ContainerInterface $container, Kernel $kernel)
    {
        $this->autoload    = $container->getParameter('behat.doctrine_data_fixtures.autoload');
        $this->fixtures    = $container->getParameter('behat.doctrine_data_fixtures.fixtures');
        $this->directories = $container->getParameter('behat.doctrine_data_fixtures.directories');
        $this->migrations  = $container->getParameter('behat.doctrine_data_fixtures.migrations');
        $this->useBackup   = $container->getParameter('behat.doctrine_data_fixtures.use_backup');
        $this->kernel      = $kernel;
    }

    /**
     * Returns the reference repository while loading the fixtures.
     *
     * @return \Doctrine\Common\DataFixtures\ReferenceRepository|null
     */
    public function getReferenceRepository()
    {
        return $this->referenceRepository;
    }

    /**
     * Lazy init
     */
    private function init()
    {
        $this->listener = new PlatformListener();

        $this->entityManager = $this->kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager->getEventManager()->addEventSubscriber($this->listener);
    }

    /**
     * Retrieve Data fixtures loader
     *
     * @return mixed
     */
    private function getFixtureLoader()
    {
        $container = $this->kernel->getContainer();

        $loader = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ? new DataFixturesLoader($container)
            : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader')
                ? new DoctrineFixturesLoader($container)
                : new SymfonyFixturesLoader($container));

        return $loader;
    }

    /**
     * Calculate hash on data fixture class names, class file names and modification timestamps
     *
     * @param array $migrations
     * @param array $fixtures
     *
     * @return string
     */
    private function generateHash($migrations, $fixtures)
    {
        array_walk($migrations, function (& $migration) {
            $migration .= '@' . filemtime($migration);
        });

        $classNames = array_map('get_class', $fixtures);

        foreach ($classNames as & $className) {
            $class    = new \ReflectionClass($className);
            $fileName = $class->getFileName();

            $className .= ':' . $fileName . '@' . filemtime($fileName);
        }

        sort($classNames);

        return sha1(serialize(array($migrations, $classNames)));
    }

    /**
     * Get bundle fixture directories
     *
     * @return array Array of directories
     */
    private function getBundleFixtureDirectories()
    {
        return array_filter(
            array_map(
                function ($bundle) {
                    $path = $bundle->getPath() . '/DataFixtures/ORM';

                    return is_dir($path) ? $path : null;
                },
                $this->kernel->getBundles()
            )
        );
    }

    /**
     * Fetch fixtures from directories
     *
     * @param array $directoryNames
     */
    private function fetchFixturesFromDirectories($directoryNames)
    {
        foreach ($directoryNames as $directoryName) {
            $this->loader->loadFromDirectory($directoryName);
        }
    }

    /**
     * Load a data fixture class.
     *
     * @param string $className Class name
     */
    private function loadFixtureClass($className)
    {
        $fixture = new $className();

        if ($this->loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $this->loader->addFixture(new $className());

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($dependency);
            }
        }
    }

    /**
     * Fetch fixtures from classes
     *
     * @param array $classNames
     */
    private function fetchFixturesFromClasses($classNames)
    {
        foreach ($classNames as $className) {
            if (substr($className, 0, 1) !== '\\') {
                $className = '\\' . $className;
            }

            if ( ! class_exists($className, false)) {
                $this->loadFixtureClass($className);
            }
        }
    }

    /**
     * Fetch fixtures
     *
     * @return array
     */
    private function fetchFixtures()
    {
        $this->loader = $this->getFixtureLoader();

        $bundleDirectories = $this->autoload ? $this->getBundleFixtureDirectories() : array();

        $this->fetchFixturesFromDirectories($bundleDirectories);
        $this->fetchFixturesFromDirectories($this->directories ?: array());
        $this->fetchFixturesFromClasses($this->fixtures ?: array());

        return $this->loader->getFixtures();
    }

    /**
     * Fetch SQL migrations
     *
     * @see https://github.com/doctrine/migrations/pull/162
     *
     * @return array
     */
    private function fetchMigrations()
    {
        if (empty($this->migrations)) {
            return array();
        }

        $migrations = array();
        $connection = $this->entityManager->getConnection();
        $driver     = $connection->getDatabasePlatform()->getName();

        foreach ($this->migrations as $migration) {
            $files = glob($migration . '/*.sql');

            if (empty($files)) {
                $files = glob($migration . '/' . $driver . '/*.sql');

                if (empty($files)) {
                    continue;
                }
            }

            foreach ($files as $file) {
                $version = basename($file, '.sql');

                if (preg_match('~^[vV]([^_]+)_~', $version, $matches)) {
                    $version = $matches[1];
                }

                $migrations[$version] = $file;
            }
        }

        uksort($migrations, 'version_compare');

        return $migrations;
    }

    /**
     * Dispatch event
     *
     * @param \Doctrine\ORM\EntityManager $em    Entity manager
     * @param string                      $event Event name
     */
    private function dispatchEvent($em, $event)
    {
        $eventArgs = new LifecycleEventArgs(null, $em);

        $em->getEventManager()->dispatchEvent($event, $eventArgs);
    }

    /**
     * Load fixtures into database
     */
    private function loadFixtures()
    {
        $em = $this->entityManager;

        $this->dispatchEvent($em, 'preTruncate');

        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        $executor = new ORMExecutor($em, $purger);

        if ( ! $this->databaseFile) {
            $executor->purge();
        }

        $this->runMigrations();

        $executor->execute($this->fixtures, true);

        $this->referenceRepository = $executor->getReferenceRepository();

        $this->dispatchEvent($em, 'postTruncate');
    }

    /**
     * Run migrations
     */
    private function runMigrations()
    {
        $connection   = $this->entityManager->getConnection();

/*
        $outputWriter = new OutputWriter(function () {});

        $configuration = new Configuration($connection, $outputWriter);
        $configuration->setMigrationsNamespace();
        $configuration->setMigrationsDirectory();
        $configuration->setName();
        $configuration->setMigrationsTableName();

        $migration = new Migration($configuration);
        $sql       = $migration->migrate(null, false);
*/

        foreach ($this->migrations as $migration) {
            foreach (explode("\n", trim(file_get_contents($migration))) as $sql) {
                $connection->executeQuery($sql);
            }
        }
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
     * Create database
     *
     * @param string  $path
     * @param boolean $create
     */
    private function createDatabase($path, $create = true)
    {
        $em = $this->entityManager;

        $schemaTool = new SchemaTool($em);

        if ($create) {
            $schemaTool->dropDatabase($path);
            $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
        }
    }

    /**
     * Cache data fixtures
     */
    public function cacheFixtures()
    {
        $this->init();

        $this->migrations   = $this->fetchMigrations();
        $this->fixtures     = $this->fetchFixtures();
        $this->databaseFile = $this->getDatabaseFile();

        if ( ! $this->databaseFile) {
            return;
        }

        $cacheDirectory     = $this->kernel->getContainer()->getParameter('kernel.cache_dir');
        $this->backupDbFile = $cacheDirectory . '/test_' . $this->generateHash($this->migrations, $this->fixtures) . '.db';
    }

    /**
     * Reload data fixtures
     */
    public function reloadFixtures()
    {
        if ( ! $this->useBackup || ! $this->databaseFile) {
            $this->loadFixtures();

            return;
        }

        if (file_exists($this->backupDbFile)) {
            copy($this->backupDbFile, $this->databaseFile);

            return;
        }

        $this->createDatabase($this->databaseFile, empty($this->migrations));
        $this->loadFixtures();

        copy($this->databaseFile, $this->backupDbFile);
    }

    /**
     * Flush entity manager
     */
    public function flush()
    {
        $em = $this->entityManager;
        $em->flush();
        $em->clear();

        $cacheDriver = $em->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }
    }
}

<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
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
    private $useBackup;
    private $backupService;

    /**
     * @var \Doctrine\Common\DataFixtures\ProxyReferenceRepository
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

        if ($this->useBackup) {
            $this->backupService = $container->get('behat.doctrine_data_fixtures.service.backup');
            $this->backupService->setCacheDir($this->kernel->getContainer()->getParameter('kernel.cache_dir'));
        }
    }

    /**
     * Returns the reference repository while loading the fixtures.
     *
     * @return \Doctrine\Common\DataFixtures\ReferenceRepository|null
     */
    public function getReferenceRepository()
    {
        if ( ! $this->referenceRepository) {
            $this->referenceRepository = new ProxyReferenceRepository($this->entityManager);
        }

        return $this->referenceRepository;
    }

    /**
     * Lazy init
     */
    private function init()
    {
        $this->listener      = new PlatformListener();
        $this->entityManager = $this->kernel->getContainer()->get('doctrine')->getManager();

        $this->entityManager->getEventManager()
                            ->addEventSubscriber($this->listener);
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

    private function getHash()
    {
        return $this->generateHash($this->migrations, $this->fixtures);
    }

    /**
     * Calculate hash on data fixture class names, class file names and modification timestamps
     *
     * @param array|null $migrations
     * @param array      $fixtures
     *
     * @return string
     */
    private function generateHash($migrations, $fixtures)
    {
        if ($migrations) {
            array_walk($migrations, function (& $migration) {
                $migration .= '@' . filemtime($migration);
            });
        }

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
        if ( ! isset($this->migrations)) {
            return null;
        }

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

        $em->getEventManager()
           ->dispatchEvent($event, $eventArgs);
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
        $executor->setReferenceRepository($this->getReferenceRepository());

        if ( ! $this->useBackup) {
            $executor->purge();
        }

        $this->runMigrations();

        $executor->execute($this->fixtures, true);

        $this->dispatchEvent($em, 'postTruncate');
    }

    /**
     * Run migrations
     */
    private function runMigrations()
    {
        if ( ! isset($this->migrations)) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        $container  = $this->kernel->getContainer();
        $namespace  = $container->getParameter('doctrine_migrations.namespace');

        if ($namespace) {
            $directory    = $container->getParameter('doctrine_migrations.dir_name');
            $outputWriter = new OutputWriter(
                function () {
                }
            );

            $configuration = new Configuration($connection, $outputWriter);
            $configuration->setMigrationsNamespace($namespace);
            $configuration->setMigrationsDirectory($directory);
            $configuration->registerMigrationsFromDirectory($directory);
            $configuration->setName($container->getParameter('doctrine_migrations.name'));
            $configuration->setMigrationsTableName($container->getParameter('doctrine_migrations.table_name'));

            $migration = new Migration($configuration);
            $migration->migrate(null, false);
        }

        foreach ($this->migrations as $migration) {
            foreach (explode("\n", trim(file_get_contents($migration))) as $sql) {
                $connection->executeQuery($sql);
            }
        }
    }

    /**
     * Create database using doctrine schema tool
     */
    private function createDatabase()
    {
        $em       = $this->entityManager;
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadata);
    }

    /**
     * Drop database using doctrine schema tool
     */
    private function dropDatabase()
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();
    }

    /**
     * Cache data fixtures
     */
    public function cacheFixtures()
    {
        $this->init();

        $this->migrations = $this->fetchMigrations();
        $this->fixtures   = $this->fetchFixtures();

        if ($this->useBackup && ! $this->hasBackup()) {
            $this->dropDatabase();
        }
    }

    /**
     * Get backup file path
     *
     * @return string
     */
    private function getBackupFile()
    {
        return $this->backupService->getBackupFile($this->getHash());
    }

    /**
     * Check if there is a backup
     *
     * @return void
     */
    private function hasBackup()
    {
        return $this->backupService->hasBackup($this->getHash());
    }

    /**
     * Create a backup for the current fixtures / migrations
     */
    private function createBackup()
    {
        $hash       = $this->getHash();
        $connection = $this->entityManager->getConnection();

        $this->backupService->createBackup($connection, $hash);
    }

    /**
     * Restore a backup for the current fixtures / migrations
     */
    private function restoreBackup()
    {
        $hash       = $this->getHash();
        $connection = $this->entityManager->getConnection();

        $this->backupService->restoreBackup($connection, $hash);
    }

    /**
     * Reload data fixtures
     */
    public function reloadFixtures()
    {
        if ( ! $this->useBackup) {
            $this->loadFixtures();

            return;
        }

        if ($this->hasBackup()) {
            $this->restoreBackup();

            $this->getReferenceRepository()
                 ->load($this->getBackupFile());

            return;
        }

        if ($this->migrations === null) {
            $this->dropDatabase();
            $this->createDatabase();
        }

        $this->loadFixtures();
        $this->createBackup();

        $this->getReferenceRepository()
             ->save($this->getBackupFile());
    }

    /**
     * Flush entity manager
     */
    public function flush()
    {
        $em = $this->entityManager;
        $em->flush();
        $em->clear();

        $this->referenceRepository = null;

        $cacheDriver = $em->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }
    }
}

<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Symfony\Component\HttpKernel\Kernel;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as SymfonyFixturesLoader;
use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor,
    Doctrine\Common\DataFixtures\Purger\ORMPurger,
    Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;

use Doctrine\ORM\Tools\SchemaTool;

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
    private $kernel;
    private $entityManager;
    private $listener;
    private $databaseFile;
    private $backupDbFile;

    /**
     * Constructor
     *
     * @param ContainerInterface $container Service container
     * @param Kernel             $kernel    Application kernel
     */
    public function __construct(ContainerInterface $container, Kernel $kernel)
    {
        $this->autoload = $container->getParameter('behat.doctrine_data_fixtures.autoload');
        $this->fixtures = $container->getParameter('behat.doctrine_data_fixtures.fixtures');
        $this->directories = $container->getParameter('behat.doctrine_data_fixtures.directories');
        $this->kernel = $kernel;
    }

    /**
     * Lazy init
     */
    private function init()
    {
        $this->listener = new PlatformListener;

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
     * Calculate hash on data fixture class names
     *
     * @param array $fixtures
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
     * Get bundle fixture directories
     *
     * @return array Array of directories
     */
    private function getBundleFixtureDirectories()
    {
        return array_filter(array_map(function ($bundle) {
            $path = $bundle->getPath() . '/DataFixtures/ORM';

            return is_dir($path) ? $path : null;
        }, $this->kernel->getBundles()));
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

        $this->loader->addFixture(new $className);

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

            if (! class_exists($className, false)) {
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
     * Dispatch event
     *
     * @param EntityManager $em    Entity manager
     * @param string        $event Event name
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
        $executor->purge();
        $executor->execute($this->fixtures, true);

        $this->dispatchEvent($em, 'postTruncate');
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

            $schemaTool = new SchemaTool($em);
            $schemaTool->dropDatabase($params['path']);
            $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
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

        $this->fixtures = $this->fetchFixtures();

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

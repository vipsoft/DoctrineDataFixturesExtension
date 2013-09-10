<?php
/**
 * @copyright 2013 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Behat\Behat\Context\ExtendedContextInterface;
use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as SymfonyFixturesLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use VIPSoft\DoctrineDataFixturesExtension\Context\FixtureAwareContextInterface;
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
    private $contextFixtures;
    private $directories;
    private $kernel;
    private $entityManager;
    private $listener;
    private $databaseFile;
    private $backupDbFile;

    /**
     * Constructor
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container Service container
     * @param \Symfony\Component\HttpKernel\Kernel                      $kernel    Application kernel
     */
    public function __construct(ContainerInterface $container, Kernel $kernel)
    {
        $this->autoload = $container->getParameter('behat.doctrine_data_fixtures.autoload');
        $this->fixtures = $container->getParameter('behat.doctrine_data_fixtures.fixtures');
        $this->directories = $container->getParameter('behat.doctrine_data_fixtures.directories');
        $this->kernel = $kernel;
        $this->contextFixtures = array();
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
     * @param array $fixtures
     *
     * @return string
     */
    private function generateHash($fixtures)
    {
        $classNames = array_map('get_class', $fixtures);

        foreach($classNames as & $className) {
            $class    = new \ReflectionClass($className);
            $fileName = $class->getFileName();

            $className .= ':' . $fileName . '@' . filemtime($fileName);
        }

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
     * Add data fixture
     *
     * @param \Doctrine\Common\DataFixtures\FixtureInterface $fixture Fixture
     */
    private function addFixture($fixture)
    {
        if ($this->loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $this->loader->addFixture($fixture);

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->addFixture(new $dependency());
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
            // FIXME: is this really a use-case that we need to support?
            if (is_object($className)) {
                $this->addFixture($className);
                continue;
            }

            if (substr($className, 0, 1) !== '\\') {
                $className = '\\' . $className;
            }

            if (! class_exists($className, false)) {
                $this->addFixture(new $className());
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
        $this->fetchFixturesFromClasses($this->contextFixtures ?: array());

        return $this->loader->getFixtures();
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
        }

        return isset($params['path']) ? $params['path'] : null;
    }

    /**
     * Create database
     *
     * @param string $path
     */
    private function createDatabase($path)
    {
        $em = $this->entityManager;

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase($path);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Check context for fixtures
     *
     * @param \Behat\Behat\Context\ExtendedContextInterface $context
     *
     * @return boolean
     */
    public function checkContextForFixtures(ExtendedContextInterface $context)
    {
        if ($context instanceof FixtureAwareContextInterface) {
            $this->contextFixtures = array_unique(
                array_merge(
                    $this->contextFixtures,
                    $context->getFixtures()
                )
            );
        }

        foreach ($context->getSubcontexts() as $subcontext) {
            $this->checkContextForFixtures($subcontext);
        }

        return (bool) $this->contextFixtures;
    }

    /**
     * Cache data fixtures
     */
    public function cacheFixtures()
    {
        $this->init();

        $this->databaseFile = $this->getDatabaseFile();

        if ($this->databaseFile && !file_exists($this->databaseFile)) {
            $this->createDatabase($this->databaseFile);
        }

        $this->fixtures = $this->fetchFixtures();

        if ($this->databaseFile) {
            $cacheDirectory = $this->kernel->getContainer()->getParameter('kernel.cache_dir');
            $this->backupDbFile = $cacheDirectory . '/test_' . $this->generateHash($this->fixtures) . '.db';
        }
    }

    /**
     * Reload data fixtures
     */
    public function reloadFixtures()
    {
        if (!$this->databaseFile) {
            $this->loadFixtures();

            return;
        }

        if (file_exists($this->backupDbFile)) {
            copy($this->backupDbFile, $this->databaseFile);

            return;
        }

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

<?php

/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\Service;

use Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader as DoctrineFixturesLoader;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as DataFixturesLoader;
use Symfony\Bundle\DoctrineFixturesBundle\Common\DataFixtures\Loader as SymfonyFixturesLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\AbstractFixtureExecutor;

/**
 * Data Fixture Service
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class FixtureService
{
    private $kernel;
    private $fixtureExecutor;
    private $objectManager;
    private $fixtures;
    private $fixtureDirectories = array();
    private $fixtureClasses = array();

    /**
     * @var ProxyReferenceRepository
     */
    private $referenceRepository;

    /**
     * Constructor
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container Service container
     * @param \Symfony\Component\HttpKernel\Kernel                      $kernel    Application kernel
     */
    public function __construct(ContainerInterface $container, Kernel $kernel, AbstractFixtureExecutor $executor)
    {
        $this->kernel = $kernel;
        $this->fixtureExecutor = $executor;

        $autoload = $container->getParameter('behat.doctrine_data_fixtures.autoload');
        $bundleDirectories = $autoload ? $this->getBundleFixtureDirectories() : array();
        $directories = $container->getParameter('behat.doctrine_data_fixtures.directories');
        $defaultDirectories = is_array($directories) ? $directories : array(); 

        $this->fixtureDirectories = array_merge($defaultDirectories, $bundleDirectories);
        $this->fixtureClasses = $container->getParameter('behat.doctrine_data_fixtures.fixtures');

        $doctrineKey = $container->getParameter('behat.doctrine_data_fixtures.doctrine_key');
        $this->objectManager = $this->kernel->getContainer()->get($doctrineKey)->getManager();

        $this->referenceRepository = new ProxyReferenceRepository($this->objectManager);
    }

    /**
     * Fetch fixtures
     *
     * @return array
     */
    public function fetchFixtures($loader)
    {
        foreach ($this->fixtureDirectories as $directoryName) {
            $loader->loadFromDirectory($directoryName);
        }

        if (!empty($this->fixtureClasses)) {
            $this->fetchFixturesFromClasses($this->fixtureClasses);
        }

        return $loader->getFixtures();
    }

    /**
     * 
     * @return array
     */
    public function getFixtures($force = false)
    {
        if ($this->fixtures === null || $force) {
            $this->fixtures = $this->fetchFixtures($this->getFixtureLoader());
        }

        return $this->fixtures;
    }

    /**
     * Load fixtures into database
     */
    public function loadFixtures()
    {
        $this->fixtureExecutor->loadFixtures(
            $this->objectManager,
            $this->referenceRepository,
            $this->getFixtures()
        );
    }

    /**
     * Flush entity manager
     */
    public function flush()
    {
        $objectManager = $this->objectManager;
        $objectManager->flush();
        $objectManager->clear();

        $cacheDriver = $objectManager->getMetadataFactory()->getCacheDriver();

        if ($cacheDriver) {
            $cacheDriver->deleteAll();
        }
    }

    /**
     * Fetch fixtures from classes
     *
     * @param array $classNames
     */
    private function fetchFixturesFromClasses(array $classNames)
    {
        foreach ($classNames as $className) {
            if (substr($className, 0, 1) !== '\\') {
                $className = '\\' . $className;
            }

            if (!class_exists($className, false)) {
                $this->loadFixtureClass($className);
            }
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

        if ($this->getLoader()->hasFixture($fixture)) {
            unset($fixture);

            return;
        }

        $this->getLoader()->addFixture(new $className());

        if ($fixture instanceof DependentFixtureInterface) {
            foreach ($fixture->getDependencies() as $dependency) {
                $this->loadFixtureClass($dependency);
            }
        }
    }

    /**
     * Retrieve Data fixtures loader
     *
     * @return mixed
     */
    private function getFixtureLoader()
    {
        $container = $this->kernel->getContainer();
        $loader = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader') ?
            new DataFixturesLoader($container) :
                (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader') ?
                    new DoctrineFixturesLoader($container) :
                    new SymfonyFixturesLoader($container));

        return $loader;
    }

    /**
     * Get bundle fixture directories
     *
     * @return array Array of directories
     */
    private function getBundleFixtureDirectories()
    {
        $fixtureExecutor = $this->fixtureExecutor;

        return array_filter(
            array_map(
                function ($bundle) use ($fixtureExecutor) {
                    $path = $bundle->getPath() . $fixtureExecutor->getFixturesPath();

                    return is_dir($path) ? $path : null;
                },
                $this->kernel->getBundles()
            )
        );
    }
}

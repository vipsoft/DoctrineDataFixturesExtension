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
        $autoload = $container->getParameter('behat.doctrine_data_fixtures.autoload');
        $fixtureClasses = $container->getParameter('behat.doctrine_data_fixtures.fixtures');

        $this->docrtineKey = $container->getParameter('behat.doctrine_data_fixtures.doctrine_key');
        $this->kernel = $kernel;
        $this->fixtureExecutor = $executor;

        $defaultDirectories = $container->getParameter('behat.doctrine_data_fixtures.directories');
        $bundleDirectories = $autoload ? $this->getBundleFixtureDirectories() : array();

        $this->fixtureExecutor->setFixtureDirectories(array_merge($defaultDirectories, $bundleDirectories));

        if (is_array($fixtureClasses)) {
            $this->fixtureExecutor->setFixtureClasses($fixtureClasses);
        }

        $this->init();
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
        $this->objectManager = $this->kernel->getContainer()->get($this->docrtineKey)->getManager();

        $this->referenceRepository = new ProxyReferenceRepository($this->objectManager);
    }

    /**
     * Retrieve Data fixtures loader
     *
     * @return mixed
     */
    private function getFixtureLoader()
    {
        $container = $this->kernel->getContainer();

        $loader = class_exists('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader') ? new DataFixturesLoader($container) : (class_exists('Doctrine\Bundle\FixturesBundle\Common\DataFixtures\Loader') ? new DoctrineFixturesLoader($container) : new SymfonyFixturesLoader($container));

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

    /**
     * Load fixtures into database
     */
    public function loadFixtures()
    {
        $this->fixtureExecutor->loadFixtures($this->objectManager, $this->getReferenceRepository(), $this->getFixtureLoader());
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
}

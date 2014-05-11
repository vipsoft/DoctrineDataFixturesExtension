<?php

namespace VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

abstract class AbstractFixtureExecutor
{
    private $loader;
    private $fixtureDirectories = array();
    private $fixtureClasses = array();

    /**
     * @param array $fixtures
     */
    abstract public function executeFixtures(
        ObjectManager $objectManager,
        ProxyReferenceRepository $referenceRepository,
        array $fixtures
    );

    /**
     * @return string
     */
    abstract public function getFixturesPath();

    /**
     * 
     * @return array
     */
    public function getFixtureDirectories()
    {
        return $this->fixtureDirectories;
    }

    /**
     * 
     * @param array $fixtureDirectories
     */
    public function setFixtureDirectories(array $fixtureDirectories)
    {
        $this->fixtureDirectories = $fixtureDirectories;
    }

    /**
     * 
     * @return array
     */
    public function getFixtureClasses()
    {
        return $this->fixtureClasses;
    }

    /**
     * 
     * @param array $fixtureClasses
     */
    public function setFixtureClasses(array $fixtureClasses)
    {
        $this->fixtureClasses = $fixtureClasses;
    }

    /**
     * @param array $fixtures
     */
    public function loadFixtures(
        ObjectManager $objectManager,
        ProxyReferenceRepository $referenceRepository,
        $loader
    ) {
        $this->setLoader($loader);

        $this->dispatchEvent($objectManager, 'preTruncate');

        $this->executeFixtures($objectManager, $referenceRepository, $this->getFixtures());

        $this->dispatchEvent($objectManager, 'postTruncate');
    }

    /**
     * @return mixed
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * @param mixed $loader
     */
    public function setLoader($loader)
    {
        $this->loader = $loader;
    }

    /**
     * Fetch fixtures
     *
     * @return array
     */
    public function fetchFixtures($loader)
    {
        foreach ($this->getFixtureDirectories() as $directoryName) {
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
    public function getFixtures()
    {
        return $this->fetchFixtures($this->getLoader());
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
}

<?php

namespace VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

abstract class AbstractFixtureExecutor
{
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
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param \Doctrine\Common\DataFixtures\ProxyReferenceRepository $referenceRepository
     * @param array $fixtures
     */
    public function loadFixtures(
        ObjectManager $objectManager,
        ProxyReferenceRepository $referenceRepository,
        array $fixtures
    ) {
        $this->dispatchEvent($objectManager, 'preTruncate');

        $this->executeFixtures($objectManager, $referenceRepository, $fixtures);

        $this->dispatchEvent($objectManager, 'postTruncate');
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

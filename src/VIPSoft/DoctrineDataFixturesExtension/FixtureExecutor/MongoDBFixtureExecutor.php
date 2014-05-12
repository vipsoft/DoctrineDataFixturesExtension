<?php

namespace VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;

class MongoDBFixtureExecutor extends AbstractFixtureExecutor
{
    /**
     * @return string
     */
    public function getFixturesPath()
    {
        return '/DataFixtures/MongoDB';
    }

    /**
     * 
     */
    public function executeFixtures(
        ObjectManager $objectManager,
        ProxyReferenceRepository $referenceRepository,
        array $fixtures
    ) {
        $purger = new MongoDBPurger($objectManager);

        $executor = new MongoDBExecutor($objectManager, $purger);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();
        $executor->execute($fixtures, true);
    }
}

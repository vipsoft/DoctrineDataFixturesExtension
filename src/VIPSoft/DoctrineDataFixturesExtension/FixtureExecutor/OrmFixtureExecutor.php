<?php

namespace VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\ProxyReferenceRepository;
use VIPSoft\DoctrineDataFixturesExtension\EventListener\OrmListener;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\ORM\Tools\SchemaTool;

class OrmFixtureExecutor extends AbstractFixtureExecutor
{
    /**
     * @var \VIPSoft\DoctrineDataFixturesExtension\EventListener\PlatformListener 
     */
    private $platformListener;

    /**
     * @var boolean
     */
    private static $inited = false;

    /**
     * 
     * @param \VIPSoft\DoctrineDataFixturesExtension\EventListener\OrmListener $ormListener
     */
    public function __construct(OrmListener $ormListener)
    {
        $this->platformListener = $ormListener;
    }

    /**
     * 
     * @return \VIPSoft\DoctrineDataFixturesExtension\EventListener\OrmListener
     */
    public function getPlatformListener()
    {
        return $this->platformListener;
    }

    /**
     * 
     * @return string
     */
    public function getFixturesPath()
    {
        return '/DataFixtures/ORM';
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param \Doctrine\Common\DataFixtures\ProxyReferenceRepository $referenceRepository
     * @param mixed $loader
     */
    public function loadFixtures(ObjectManager $objectManager, ProxyReferenceRepository $referenceRepository, $loader)
    {
        $this->initListener($objectManager);

        parent::loadFixtures($objectManager, $referenceRepository, $loader);
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @param \Doctrine\Common\DataFixtures\ProxyReferenceRepository $referenceRepository
     * @param array $fixtures
     */
    public function executeFixtures(
        ObjectManager $objectManager,
        ProxyReferenceRepository $referenceRepository,
        array $fixtures
    ) {
        $purger = new ORMPurger($objectManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);

        $executor = new ORMExecutor($objectManager, $purger);
        $executor->setReferenceRepository($referenceRepository);
        $executor->purge();
        $executor->execute($fixtures, true);
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\ObjectManager $objectManager
     * @return boolean
     */
    private function initListener(ObjectManager $objectManager)
    {
        if (self::$inited) {
            return false;
        }

        $objectManager->getEventManager()->addEventSubscriber($this->getPlatformListener());

        self::$inited = true;
    }
}

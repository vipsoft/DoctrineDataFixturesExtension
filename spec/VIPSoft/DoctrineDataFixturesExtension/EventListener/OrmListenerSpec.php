<?php

namespace spec\VIPSoft\DoctrineDataFixturesExtension\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OrmListenerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('VIPSoft\DoctrineDataFixturesExtension\EventListener\OrmListener');
    }
    
    /**
     * 
     * @param Doctrine\Common\Persistence\Event\LifecycleEventArgs $eventArgs
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\DBAL\Connection $conn
     * @param \Doctrine\DBAL\Platforms\MySqlPlatform $platform
     */
    function it_should_disable_foreign_key_checks_before_load_fixtures($eventArgs, $em, $conn, $platform)
    {
        $eventArgs->getObjectManager()->willReturn($em);
        $em->getConnection()->willReturn($conn);
        $conn->getDatabasePlatform()->willReturn($platform);
        $conn->exec('SET foreign_key_checks = 0;')->shouldBeCalled();

        $this->preTruncate($eventArgs);
    }
}

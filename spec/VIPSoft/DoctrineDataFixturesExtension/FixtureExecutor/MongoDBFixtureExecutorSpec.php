<?php

namespace spec\VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MongoDBFixtureExecutorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\MongoDBFixtureExecutor');
    }
    
    /**
     * @param Doctrine\Common\DataFixtures\Loader $loader
     */
    function it_should_fetch_fuxtures_from_directories($loader)
    {
        $this->setFixtureDirectories(['/some/dir']);
        $loader->getFixtures(Argument::any())->willReturn();
        $loader->loadFromDirectory('/some/dir')->shouldBeCalled();

        $this->fetchFixtures($loader);
    }
}

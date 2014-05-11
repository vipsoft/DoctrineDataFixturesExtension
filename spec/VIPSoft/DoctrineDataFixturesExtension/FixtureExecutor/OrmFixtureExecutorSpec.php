<?php

namespace spec\VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OrmFixtureExecutorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\OrmFixtureExecutor');
    }

    /**
     * 
     * @param \VIPSoft\DoctrineDataFixturesExtension\EventListener\OrmListener $listener
     */
    function let($listener)
    {
        $this->beConstructedWith($listener);
    }
}

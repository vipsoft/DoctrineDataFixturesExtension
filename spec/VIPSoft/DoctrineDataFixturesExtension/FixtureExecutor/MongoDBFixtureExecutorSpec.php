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
}

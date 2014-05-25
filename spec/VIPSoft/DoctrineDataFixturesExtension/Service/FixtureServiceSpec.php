<?php

namespace spec\VIPSoft\DoctrineDataFixturesExtension\Service;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FixtureServiceSpec extends ObjectBehavior
{
    private $executor;
    private $objectManager;

    /**
     * @param Symfony\Component\HttpKernel\Kernel $kernel
     * @param Symfony\Component\DependencyInjection\ContainerInterface $kernelContainer
     * @param VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\AbstractFixtureExecutor $fixtureExecutor
     * @param Doctrine\ORM\EntityManager $em
     */
    function let($kernel, $kernelContainer, $fixtureExecutor, $em)
    {
        $this->executor = $fixtureExecutor;
        $this->objectManager = $em;

        $kernelContainer->get('some_manager_id')->willReturn($this->objectManager);

        $kernel->getBundles()->willReturn(array());
        $kernel->getContainer()->willReturn($kernelContainer);

        $this->beConstructedWith($kernel, $fixtureExecutor, $this->getOptions());
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService');
    }

    function it_should_call_fixtures_executor()
    {
        $referenceRepositoryArg = Argument::type('Doctrine\Common\DataFixtures\ProxyReferenceRepository');

        $this->executor->loadFixtures($this->objectManager, $referenceRepositoryArg, array())->shouldBeCalled();

        $this->loadFixtures();
    }

    /**
     * @return Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getOptions()
    {
        return [
            'autoload' => true,
            'directories' => array(),
            'fixtures' => array(),
            'model_manager_id' => 'some_manager_id'
        ];
    }
}

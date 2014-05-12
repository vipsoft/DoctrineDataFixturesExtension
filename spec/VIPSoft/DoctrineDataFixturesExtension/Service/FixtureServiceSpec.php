<?php

namespace spec\VIPSoft\DoctrineDataFixturesExtension\Service;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FixtureServiceSpec extends ObjectBehavior
{
    private $executor;
    private $objectManager;
    
    function it_is_initializable()
    {
        $this->shouldHaveType('VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService');
    }
    
    /**
     * @param Symfony\Component\HttpKernel\Kernel $kernel
     * @param Symfony\Component\DependencyInjection\ContainerInterface $kernelContainer
     * @param VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\AbstractFixtureExecutor $fixtureExecutor
     * @param Doctrine\Bundle\DoctrineBundle\Registry $doctrineRegistry
     * @param Doctrine\ORM\EntityManager $em
     */
    function let($kernel, $kernelContainer, $fixtureExecutor, $doctrineRegistry, $em)
    {
        $this->executor = $fixtureExecutor;
        $this->objectManager = $em;
        $container = $this->getContainer();
        
        $doctrineRegistry->getManager()->willReturn($em);
        $kernelContainer->get('doctrine')->willReturn($doctrineRegistry);

        $kernel->getBundles()->willReturn(array());
        $kernel->getContainer()->willReturn($kernelContainer);

        $this->beConstructedWith($container, $kernel, $fixtureExecutor);
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
    private function getContainer()
    {
        $prophet = new \Prophecy\Prophet();
        $container = $prophet->prophesize('Symfony\Component\DependencyInjection\ContainerInterface');
        
        $container->getParameter('behat.doctrine_data_fixtures.autoload')->willReturn(true);
        $container->getParameter('behat.doctrine_data_fixtures.doctrine_key')->willReturn('doctrine');
        $container->getParameter('behat.doctrine_data_fixtures.directories')->willReturn(array());
        $container->getParameter('behat.doctrine_data_fixtures.fixtures')->willReturn(array());
        
        return $container;
    }
}

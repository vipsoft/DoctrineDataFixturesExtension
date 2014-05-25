<?php
/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Doctrine data fixtures extension for Behat class.
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class Extension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'doctrine_data_fixtures';
    }

    /**
      * {@inheritdoc}
      */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $supportedDrivers = array('orm', 'mongodb');

        $builder
            ->requiresAtLeastOneElement()
            ->validate()
                ->ifTrue(function($config) use ($supportedDrivers) {
                    return count(array_diff(array_keys($config), $supportedDrivers)) > 0;
                })
                ->thenInvalid('Unknown behat fixture drivers. Available '.json_encode($supportedDrivers))
            ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('autoload')
                        ->defaultValue(true)
                    ->end()
                    ->arrayNode('directories')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('fixtures')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('lifetime')
                        ->defaultValue('feature')
                        ->validate()
                            ->ifNotInArray(array('feature', 'scenario'))
                            ->thenInvalid('Invalid fixtures lifetime "%s"')
                        ->end()
                    ->end()
                ->end()
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.xml');

        foreach ($config as $dbDriver => $driverOptions) {
            if (!array_key_exists('model_manager_id', $driverOptions)) {
                $driverOptions['model_manager_id'] = $container->getParameter('behat.doctrine_data_fixtures.model_manager_id.'.$dbDriver);
            }

            $this->createDriverServices($container, $dbDriver, $driverOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string $dbDriver
     * @param array $options
     */
    private function createDriverServices(ContainerBuilder $container, $dbDriver, array $options)
    {
        $loaderDefinition = new Definition(
            $container->getParameter('behat.doctrine_data_fixtures.service.fixture_loader.class'),
            array(
                new Reference('symfony2_extension.kernel'),
                new Reference('behat.doctrine_data_fixtures.fixtures_executor.'.$dbDriver),
                $options
            )
        );

        $loaderReferenceName = 'behat.doctrine_data_fixtures.fixture_loader.' . $dbDriver;
        $container->setDefinition($loaderReferenceName, $loaderDefinition);

        $contextInitializerDefinition = new Definition(
            $container->getParameter('behat.doctrine_data_fixtures.initializer.fixture_service_aware.class'),
            array(
                new Reference($loaderReferenceName)
            )
        );
        $contextInitializerDefinition->addTag('context.initializer');
        $container->setDefinition(
            'behat.doctrine_data_fixtures.initializer.fixture_service_aware.'.$dbDriver,
            $contextInitializerDefinition
        );

        $listenerDefinition = new Definition(
            $container->getParameter('behat.doctrine_data_fixtures.service.hook_listener.class'),
            array(
                new Reference($loaderReferenceName),
                $options['lifetime']
            )
        );
        $listenerDefinition->addTag('event_dispatcher.subscriber');
        $container->setDefinition(
            'behat.doctrine_data_fixtures.service.'.$dbDriver.'.hook_listener',
            $listenerDefinition
        );
    }
}

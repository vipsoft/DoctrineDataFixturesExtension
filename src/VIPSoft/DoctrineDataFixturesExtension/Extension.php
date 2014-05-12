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
            ->addDefaultsIfNotSet()
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
                ->scalarNode('db_driver')
                    ->validate()
                        ->ifNotInArray($supportedDrivers)
                        ->thenInvalid('The driver %s is not supported. Please choose one of '.json_encode($supportedDrivers))
                    ->end()
                    ->defaultValue('orm')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.xml');
        $loader->load($config['db_driver'].'.xml');

        if (isset($config['autoload'])) {
            $container->setParameter('behat.doctrine_data_fixtures.autoload', $config['autoload']);
        }

        if (isset($config['directories'])) {
            $container->setParameter('behat.doctrine_data_fixtures.directories', $config['directories']);
        }

        if (isset($config['fixtures'])) {
            $container->setParameter('behat.doctrine_data_fixtures.fixtures', $config['fixtures']);
        }

        $container->setParameter(
            'behat.doctrine_data_fixtures.use_backup',
            isset($config['use_backup']) ? $config['use_backup'] : true
        );

        $container->setParameter('behat.doctrine_data_fixtures.lifetime', $config['lifetime']);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}

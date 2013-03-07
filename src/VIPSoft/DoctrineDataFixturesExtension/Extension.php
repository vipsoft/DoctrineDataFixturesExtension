<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension;

use Behat\Behat\Extension\ExtensionInterface;
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
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/services'));
        $loader->load('core.xml');

        if (isset($config['autoload'])) {
            $container->setParameter('behat.doctrine_data_fixtures.autoload', $config['autoload']);
        }
        if (isset($config['directories'])) {
            $container->setParameter('behat.doctrine_data_fixtures.directories', $config['directories']);
        }
        if (isset($config['fixtures'])) {
            $container->setParameter('behat.doctrine_data_fixtures.fixtures', $config['fixtures']);
        }

        $container->setParameter('behat.doctrine_data_fixtures.lifetime', $config['lifetime']);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                scalarNode('autoload')->
                    defaultValue(true)->
                end()->
                arrayNode('directories')->
                    prototype('scalar')->end()->
                end()->
                arrayNode('fixtures')->
                    prototype('scalar')->end()->
                end()->
                scalarNode('lifetime')->
                    defaultValue('feature')->
                    validate()->
                        ifNotInArray(array('feature', 'scenario'))->
                        thenInvalid('Invalid fixtures lifetime "%s"')->
                    end()->
                end()->
            end()->
        end();
    }

    /**
     * {@inheritdoc}
     */
    public function getCompilerPasses()
    {
        return array(
        );
    }
}

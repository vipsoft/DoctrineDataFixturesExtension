<?php
/**
 * @copyright 2014 Anthon Pang
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
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                scalarNode('autoload')->
                    defaultValue(true)->
                end()->
                variableNode('migrations')->
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
                booleanNode('use_backup')->
                    defaultValue(true)->
                end()->
            end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/services'));
        $loader->load('core.xml');

        if (isset($config['migrations'])) {
            $config['migrations'] = (array) $config['migrations'];

            if ( ! class_exists('Doctrine\DBAL\Migrations\Migration')) {
               throw new \RuntimeException('Configuration requires doctrine/migrations package');
            }
        }

        $container->setParameter('behat.doctrine_data_fixtures.autoload', $config['autoload']);
        $container->setParameter('behat.doctrine_data_fixtures.directories', $config['directories']);
        $container->setParameter('behat.doctrine_data_fixtures.fixtures', $config['fixtures']);
        $container->setParameter('behat.doctrine_data_fixtures.lifetime', $config['lifetime']);
        $container->setParameter('behat.doctrine_data_fixtures.migrations', $config['migrations']);
        $container->setParameter('behat.doctrine_data_fixtures.use_backup', $config['use_backup']);
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

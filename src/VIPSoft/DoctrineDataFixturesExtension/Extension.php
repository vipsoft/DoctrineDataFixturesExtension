<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension;

use Symfony\Component\Config\FileLocator,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use Behat\Behat\Extension\ExtensionInterface;

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

        if (isset($config['fixtures'])) {
            $container->setParameter('behat.doctrine_data_fixtures.fixtures', $config['fixtures']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(ArrayNodeDefinition $builder)
    {
        $builder->
            children()->
                arrayNode('fixtures')->
                    prototype('scalar')->end()->
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

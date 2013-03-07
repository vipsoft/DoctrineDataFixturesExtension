<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;

/**
 * Platform listener
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class PlatformListener implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'preTruncate',
            'postTruncate',
        );
    }

    /**
     * Pre-truncate
     *
     * @param LifecyleEventArgs $args
     */
    public function preTruncate(LifecycleEventArgs $args)
    {
        $connection = $args->getObjectManager()->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $connection->exec('SET foreign_key_checks = 0;');
        }
    }

    /**
     * Post-truncate
     *
     * @param LifecyleEventArgs $args
     */
    public function postTruncate(LifecycleEventArgs $args)
    {
        $connection = $args->getObjectManager()->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $connection->exec('SET foreign_key_checks = 1;');
        }
    }
}

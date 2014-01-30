<?php
/**
 * @copyright 2014 Anthon Pang
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
     * @param \Doctrine\Common\Persistence\Event\LifecycleEventArgs $args
     */
    public function preTruncate(LifecycleEventArgs $args)
    {
        $connection = $args->getObjectManager()->getConnection();
        $platform   = $connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $connection->exec('SET foreign_key_checks = 0;');
        }
    }

    /**
     * Post-truncate
     *
     * @param \Doctrine\Common\Persistence\Event\LifecyleEventArgs $args
     */
    public function postTruncate(LifecycleEventArgs $args)
    {
        $connection = $args->getObjectManager()->getConnection();
        $platform   = $connection->getDatabasePlatform();

        if ($platform instanceof MySqlPlatform) {
            $connection->exec('SET foreign_key_checks = 1;');
        }
    }
}

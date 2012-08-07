<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Event\FeatureEvent;

/**
 * Hook listener
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class HookListener implements EventSubscriberInterface
{
    private $fixtureService;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeSuite',
            'beforeFeature',
            'afterFeature',
        );

        return array_combine($events, $events);
    }

    /**
     * Set fixture service
     *
     * @param object $service
     */
    public function setFixtureService($service)
    {
        $this->fixtureService = $service;
    }

    /**
     * Listens to "suite.before" event.
     *
     * @param SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $this->fixtureService
             ->cacheFixtures();
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param FeatureEvent $event
     */
    public function beforeFeature(FeatureEvent $event)
    {
        $this->fixtureService
             ->reloadFixtures();
    }

    /**
     * Listens to "feature.after" event.
     *
     * @param FeatureEvent $event
     */
    public function afterFeature(FeatureEvent $event)
    {
        $this->fixtureService
             ->flush();
    }
}

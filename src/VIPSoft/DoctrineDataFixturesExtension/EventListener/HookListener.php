<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\EventListener;

use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Event\OutlineExampleEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\SuiteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hook listener
 *
 * @author Anthon Pang <apang@softwaredevelopment.ca>
 */
class HookListener implements EventSubscriberInterface
{
    /**
     * @var string feature|scenario
     */
    private $lifetime;

    /**
     * @var object
     */
    private $fixtureService;

    public function __construct($lifetime)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'beforeSuite',
            'beforeFeature',
            'afterFeature',
            'beforeScenario',
            'afterScenario',
            'beforeOutlineExample',
            'afterOutlineExample',
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
        if ('feature' !== $this->lifetime) {
            return;
        }

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
        if ('feature' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->flush();
    }

    /**
     * Listens to "scenario.before" event.
     *
     * @param ScenarioEvent $event
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->reloadFixtures();
    }

    /**
     * Listens to "scenario.after" event.
     *
     * @param ScenarioEvent $event
     */
    public function afterScenario(ScenarioEvent $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->flush();
    }

    /**
     * Listens to "outline.example.before" event.
     *
     * @param OutlineExampleEvent $event
     */
    public function beforeOutlineExample(OutlineExampleEvent $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->reloadFixtures();
    }

    /**
     * Listens to "outline.example.after" event.
     *
     * @param OutlineExampleEvent $event
     */
    public function afterOutlineExample(OutlineExampleEvent $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->flush();
    }
}

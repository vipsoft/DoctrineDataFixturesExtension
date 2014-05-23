<?php
/**
 * @copyright 2014 Anthon Pang
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

    /**
     * Constructor
     *
     * @param string $lifetime
     */
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
     * @param \VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService $service
     */
    public function setFixtureService($service)
    {
        $this->fixtureService = $service;
    }

    /**
     * Listens to "suite.before" event.
     *
     * @param \Behat\Behat\Event\SuiteEvent $event
     */
    public function beforeSuite(SuiteEvent $event)
    {
        $this->fixtureService
             ->cacheFixtures();
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param \Behat\Behat\Event\FeatureEvent $event
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
     * @param \Behat\Behat\Event\FeatureEvent $event
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
     * @param \Behat\Behat\Event\ScenarioEvent $event
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
     * @param \Behat\Behat\Event\ScenarioEvent $event
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
     * @param \Behat\Behat\Event\OutlineExampleEvent $event
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
     * @param \Behat\Behat\Event\OutlineExampleEvent $event
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

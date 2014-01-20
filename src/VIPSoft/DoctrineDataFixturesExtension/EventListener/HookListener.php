<?php
/**
 * @copyright 2012 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\EventListener;

use Behat\Behat\Tester\Event\AbstractScenarioTested;
use Behat\Behat\Tester\Event\ExampleTested;
use Behat\Behat\Tester\Event\FeatureTested;
use Behat\Behat\Tester\Event\ScenarioTested;
use Behat\Testwork\Tester\Event\ExerciseCompleted;
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
        return array(
            ExerciseCompleted::BEFORE => 'beforeExercise',
            FeatureTested::BEFORE     => 'beforeFeature',
            FeatureTested::AFTER      => 'afterFeature',
            ExampleTested::BEFORE     => 'beforeScenario',
            ScenarioTested::BEFORE    => 'beforeScenario',
            ExampleTested::AFTER      => 'afterScenario',
            ScenarioTested::AFTER     => 'afterScenario',
        );
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
     * Listens to "exercise.before" event.
     *
     * @param ExerciseTested $event
     */
    public function beforeExercise(ExerciseTested $event)
    {
        $this->fixtureService
             ->cacheFixtures();
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param FeatureTested $event
     */
    public function beforeFeature(FeatureTested $event)
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
     * @param FeatureTested $event
     */
    public function afterFeature(FeatureTested $event)
    {
        if ('feature' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->flush();
    }

    /**
     * Listens to "scenario.before" and "outline.example.before" event.
     *
     * @param AbstractScenarioTested $event
     */
    public function beforeScenario(AbstractScenarioTested $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->reloadFixtures();
    }

    /**
     * Listens to "scenario.after" and "outline.example.after" event.
     *
     * @param AbstractScenarioTested $event
     */
    public function afterScenario(AbstractScenarioTested $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService
             ->flush();
    }
}

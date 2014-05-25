<?php

/**
 * @copyright 2014 Anthon Pang
 * @license MIT
 */

namespace VIPSoft\DoctrineDataFixturesExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService;
use VIPSoft\DoctrineDataFixturesExtension\FixtureExecutor\AbstractFixtureExecutor;

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
     * @var \VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService
     */
    private $fixtureService;

    /**
     * 
     * @param \VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService $fixtureService
     * @param string $lifetime
     */
    public function __construct(FixtureService $fixtureService, $lifetime)
    {
        $this->fixtureService = $fixtureService;
        $this->lifetime = $lifetime;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ExerciseCompleted::BEFORE => 'beforeExercise',
            FeatureTested::BEFORE => 'beforeFeature',
            FeatureTested::AFTER => 'afterFeature',
            ExampleTested::BEFORE => 'beforeScenario',
            ScenarioTested::BEFORE => 'beforeScenario',
            ExampleTested::AFTER => 'afterScenario',
            ScenarioTested::AFTER => 'afterScenario',
        );
    }

    /**
     * Listens to "exercise.before" event.
     *
     * @param \Behat\Testwork\Tester\Event\ExerciseCompleted $event
     */
    public function beforeExercise(ExerciseCompleted $event)
    {
        
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param \Behat\Behat\Tester\Event\FeatureTested $event
     */
    public function beforeFeature(FeatureTested $event)
    {
        if ('feature' !== $this->lifetime) {
            return;
        }

        $this->fixtureService->loadFixtures();
    }

    /**
     * Listens to "feature.after" event.
     *
     * @param \Behat\Behat\Tester\Event\FeatureTested $event
     */
    public function afterFeature(FeatureTested $event)
    {
        if ('feature' !== $this->lifetime) {
            return;
        }

        $this->fixtureService->flush();
    }

    /**
     * Listens to "scenario.before" and "outline.example.before" event.
     *
     * @param \Behat\Behat\Tester\Event\AbstractScenarioTested $event
     */
    public function beforeScenario(ScenarioTested $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService->loadFixtures();
    }

    /**
     * Listens to "scenario.after" and "outline.example.after" event.
     *
     * @param \Behat\Behat\Tester\Event\AbstractScenarioTested $event
     */
    public function afterScenario(ScenarioTested $event)
    {
        if ('scenario' !== $this->lifetime) {
            return;
        }

        $this->fixtureService->flush();
    }
}

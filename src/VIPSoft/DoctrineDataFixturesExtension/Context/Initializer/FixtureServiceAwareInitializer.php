<?php

namespace VIPSoft\DoctrineDataFixturesExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use VIPSoft\DoctrineDataFixturesExtension\Context\FixtureServiceAwareContextInterface;
use VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService;

/**
 * Class FixtureServiceAwareInitializer
 *
 * @package VIPSoft\DoctrineDataFixturesExtension\Context\Initializer
 */
class FixtureServiceAwareInitializer implements ContextInitializer
{
    /**
     * @var FixtureService
     */
    private $fixtureService;

    /**
     * Constructor
     *
     */
    public function __construct(FixtureService $fixtureService)
    {
        $this->fixtureService = $fixtureService;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof FixtureServiceAwareContextInterface && !$this->usesReferenceDictionary($context)) {
            return;
        }

        $context->setFixtureService($this->fixtureService);
    }

    /**
     * Checks whether the context uses the ReferenceDictionary trait.
     *
     * @param Context $context
     *
     * @return boolean
     */
    private function usesReferenceDictionary(Context $context)
    {
        $refl = new \ReflectionObject($context);

        if (! method_exists($refl, 'getTraitNames')) {
            return false;
        }

        if (! in_array('VIPSoft\DoctrineDataFixturesExtension\Context\ReferenceDictionary', $refl->getTraitNames())) {
            return false;
        }

        return true;
    }

}

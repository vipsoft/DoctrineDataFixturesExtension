<?php
/**
 * @author Thomas Ploch <thomas.ploch@meinfernbus.de>
 */
namespace VIPSoft\DoctrineDataFixturesExtension\Context;

/**
 * Interface FixtureAwareContextInterface
 *
 * Use this interface to autoload datafixtures in beforeScenario events.
 */
interface FixtureAwareContextInterface
{
    /**
     * This method should return an array with DataFixture classes to load
     *
     * Example:
     * <pre>
     * return array(
     *     'FQCN\For\Fixture\Class',
     *     'FQCN\For\Another\Fixture\Class'
     * )
     * </pre>
     *
     * @return array
     */
    public function getFixtures();
}

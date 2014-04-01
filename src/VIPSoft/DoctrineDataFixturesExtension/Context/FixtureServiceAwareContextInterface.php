<?php

namespace VIPSoft\DoctrineDataFixturesExtension\Context;

use VIPSoft\DoctrineDataFixturesExtension\Service\FixtureService;

/**
 * Interface FixtureServiceAwareContextInterface
 *
 * @package VIPSoft\DoctrineDataFixturesExtension\Context
 */
interface FixtureServiceAwareContextInterface
{
    /**
     * Set the FixtureService
     *
     * @param FixtureService $service
     * @return mixed
     */
    public function setFixtureService(FixtureService $service);
}

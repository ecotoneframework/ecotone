<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\NotUniqueHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class ServiceQueryHandlersWithAllowedNotUniqueClassAndInputChannels
{
    /**
     * @CommandHandler("some1")
     * @NotUniqueHandler()
     */
    public function execute1(\stdClass $class) : int
    {

    }

    /**
     * @CommandHandler("some2")
     * @NotUniqueHandler()
     */
    public function execute2(\stdClass $class) : int
    {

    }
}
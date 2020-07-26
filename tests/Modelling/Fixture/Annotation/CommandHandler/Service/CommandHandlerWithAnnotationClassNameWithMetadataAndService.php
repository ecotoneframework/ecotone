<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class CommandHandlerWithAnnotationClassNameWithMetadataAndService
{
    /**
     * @param array $metadata
     * @param \stdClass $service
     * @return int
     * @CommandHandler(inputChannelName="input", endpointId="command-id", ignorePayload=true)
     */
    public function execute(array $metadata, \stdClass $service) : int
    {
        return 1;
    }
}
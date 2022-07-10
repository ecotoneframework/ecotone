<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;

class CommandHandlerWithAnnotationClassNameWithMetadataAndService
{
    #[CommandHandler("input", "command-id")]
    #[IgnorePayload]
    public function execute(array $metadata, \stdClass $service) : int
    {
        return 1;
    }
}
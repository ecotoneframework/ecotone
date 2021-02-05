<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\IgnorePayload;

class CommandHandlerWithAnnotationClassNameWithMetadataAndService
{
    #[CommandHandler("input", "command-id")]
    #[IgnorePayload]
    public function execute(array $metadata, \stdClass $service) : int
    {
        return 1;
    }
}
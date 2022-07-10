<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection;

use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ProjectionConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return [
            ProjectionRunningConfiguration::createEventDriven(InProgressTicketList::IN_PROGRESS_TICKET_PROJECTION)
                ->withTestingSetup()
        ];
    }
}
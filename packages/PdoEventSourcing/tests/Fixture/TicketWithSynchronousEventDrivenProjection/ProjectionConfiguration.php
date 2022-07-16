<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketWithSynchronousEventDrivenProjection;

use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

class ProjectionConfiguration
{
    #[ServiceContext]
    public function configureProjection()
    {
        return [
            ProjectionRunningConfiguration::createEventDriven(InProgressTicketList::IN_PROGRESS_TICKET_PROJECTION)
                ->withTestingSetup(),
        ];
    }
}

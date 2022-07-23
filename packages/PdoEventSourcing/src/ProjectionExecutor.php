<?php

namespace Ecotone\EventSourcing;

use Ecotone\Modelling\Event;

interface ProjectionExecutor
{
    /**
     * @return array|null new generated state
     */
    public function executeWith(string $eventName, Event $event, ?array $state = null): ?array;
}

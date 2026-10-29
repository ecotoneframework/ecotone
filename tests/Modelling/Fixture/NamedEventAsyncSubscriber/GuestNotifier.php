<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\NamedEventAsyncSubscriber;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Test\Ecotone\Modelling\Fixture\NamedEvent\GuestWasAddedToBook;

final class GuestNotifier
{
    #[Asynchronous('async')]
    #[EventHandler(GuestWasAddedToBook::EVENT_NAME, endpointId: 'guestViewer.notify1')]
    public function notify1(GuestWasAddedToBook $event)
    {

    }

    #[Asynchronous('async')]
    #[EventHandler(GuestWasAddedToBook::EVENT_NAME, endpointId: 'guestViewer.notify2')]
    public function notify2(GuestWasAddedToBook $event)
    {

    }

    #[Asynchronous('background')]
    #[EventHandler(GuestWasAddedToBook::EVENT_NAME, endpointId: 'guestViewer.notify3')]
    public function notify3(GuestWasAddedToBook $event)
    {

    }
}

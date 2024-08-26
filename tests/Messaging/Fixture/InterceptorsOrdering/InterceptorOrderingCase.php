<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Modelling\Attribute\CommandHandler;

class InterceptorOrderingCase
{
    #[CommandHandler(routingKey: 'commandEndpointReturning')]
    public function command(#[Reference] InterceptorOrderingStack $stack): string
    {
        $stack->add('endpoint');
        return 'something';
    }

    #[CommandHandler(routingKey: 'commandEndpointVoid')]
    public function commandVoid(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('endpoint');
    }

    #[ServiceActivator(inputChannelName: 'serviceEndpointReturning')]
    public function serviceActivator(#[Reference] InterceptorOrderingStack $stack): string
    {
        $stack->add('endpoint');
        return 'something';
    }

    #[ServiceActivator(inputChannelName: 'serviceEndpointVoid')]
    public function voidEndpoint(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('endpoint');
    }

    #[CommandHandler(routingKey: 'commandWithOutputChannel', outputChannelName: 'internal-channel')]
    public function commandWithOutputChannel(#[Reference] InterceptorOrderingStack $stack): string
    {
        $stack->add('endpoint');
        return 'something';
    }

    #[InternalHandler(inputChannelName: 'internal-channel')]
    public function commandOutputChannel(#[Reference] InterceptorOrderingStack $stack): string
    {
        $stack->add('command-output-channel');

        return 'something';
    }
}

<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Parameter\Reference;

/**
 * licence Apache-2.0
 */
final class OutputHandler
{
    #[InternalHandler(inputChannelName: 'internal-channel')]
    public function output(#[Reference] InterceptorOrderingStack $stack): mixed
    {
        $stack->add('command-output-channel');

        return 'something';
    }
}

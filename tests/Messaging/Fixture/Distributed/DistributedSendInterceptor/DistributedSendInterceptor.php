<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedSendInterceptor;

use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Modelling\DistributedBus;

final class DistributedSendInterceptor
{
    #[Before(pointcut: DistributedBus::class, changeHeaders: true)]
    public function addHeaders(): array
    {
        return [
            'extra' => '123a',
        ];
    }
}

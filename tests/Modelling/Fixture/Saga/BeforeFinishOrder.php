<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Saga;

use Ecotone\Messaging\Attribute\Interceptor\Before;

final class BeforeFinishOrder
{
    #[Before(pointcut: 'Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment::finishOrder', changeHeaders: true)]
    public function enrich(PaymentWasDoneEvent $event): array
    {
        return [
            'paymentId' => $event->paymentId,
        ];
    }
}

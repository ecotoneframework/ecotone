<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Saga;

use Ecotone\Messaging\Attribute\Interceptor\Presend;

final class PresendFinishOrder
{
    #[Presend(pointcut: 'Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment::finishOrder', changeHeaders: true)]
    public function enrich(PaymentWasDoneEvent $event): array
    {
        return [
            'paymentId' => $event->paymentId,
        ];
    }

    #[Presend(pointcut: 'Test\Ecotone\Modelling\Fixture\Saga\AsynchronousOrderFulfilment::finishOrder', changeHeaders: true)]
    public function enrichAsync(PaymentWasDoneEvent $event): array
    {
        return [
            'paymentId' => $event->paymentId,
        ];
    }
}

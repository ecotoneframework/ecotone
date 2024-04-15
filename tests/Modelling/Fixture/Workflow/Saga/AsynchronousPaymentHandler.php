<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command\TakePayment;

final class AsynchronousPaymentHandler
{
    public function __construct(private bool $isPaymentTaken = false)
    {
    }

    #[Asynchronous('async')]
    #[CommandHandler('takePayment', endpointId: 'takePaymentEndpoint')]
    public function takePayment(TakePayment $command): void
    {
        $this->isPaymentTaken = true;
    }

    #[QueryHandler('isPaymentTaken')]
    public function isPaymentTaken(): bool
    {
        return $this->isPaymentTaken;
    }
}

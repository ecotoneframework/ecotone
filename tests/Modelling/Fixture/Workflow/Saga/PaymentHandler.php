<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command\TakePayment;

final class PaymentHandler
{
    public function __construct(private bool $isPaymentTaken = false)
    {
    }

    #[CommandHandler('takePayment')]
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

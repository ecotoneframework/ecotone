<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\FailureHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Messaging\ErrorMessage;

final class FailureErrorHandler
{
    private ?ErrorMessage $message = null;

    #[Asynchronous('async')]
    #[InternalHandler('errorHandler', endpointId: 'errorHandlerEndpoint')]
    public function handle(ErrorMessage $message): void
    {
        $this->message = $message;
    }

    public function getMessage(): ?ErrorMessage
    {
        return $this->message;
    }
}

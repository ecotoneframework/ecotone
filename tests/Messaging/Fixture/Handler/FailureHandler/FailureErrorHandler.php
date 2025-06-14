<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\FailureHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Support\ErrorMessage;

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

<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
class ErrorReceiver
{
    /**
     * @var string|null
     */
    private $errorOrder;

    #[InternalHandler(ErrorConfigurationContext::DEAD_LETTER_CHANNEL)]
    public function receiveError(Message $message): void
    {
        $this->errorOrder = $message->getPayload();
    }

    #[InternalHandler('getErrorMessage')]
    public function getErrorOrder(): ?string
    {
        return $this->errorOrder ? $this->errorOrder : null;
    }
}

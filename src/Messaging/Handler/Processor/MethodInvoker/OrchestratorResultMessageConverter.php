<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Enterprise
 */
class OrchestratorResultMessageConverter implements ResultToMessageConverter
{
    public function __construct(private string $headerName)
    {
    }

    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        if ($result === null) {
            return $requestMessage;
        }

        if (! is_array($result)) {
            throw InvalidArgumentException::create('Orchestrator must return array of strings, but returned ' . gettype($result));
        }

        foreach ($result as $index => $item) {
            if (! is_string($item)) {
                throw InvalidArgumentException::create('Orchestrator returned array must contain only strings, but found ' . gettype($item) . " at index {$index}");
            }
        }

        return MessageBuilder::fromMessage($requestMessage)
            ->prependRoutingSlip($result)
            ->build();
    }
}

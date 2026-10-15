<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;

use Closure;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\Gateway\GatewayInternalProcessor;
use Ecotone\Modelling\Config\MessageBusChannel;
use Throwable;

/**
 * licence Apache-2.0
 */
final class MessageCausation
{
    private function __construct(private ?string $messageKind, private string $messageName)
    {
    }

    public static function from(MethodInvocation|Closure $methodInvocation, Message $message): self
    {
        $gatewayInterface = $methodInvocation instanceof MethodInvocation && $methodInvocation->getObjectToInvokeOn() instanceof GatewayInternalProcessor
            ? $methodInvocation->getObjectToInvokeOn()->getInterfaceToCallName()
            : '';

        $messageKind = match (true) {
            str_starts_with($gatewayInterface, CommandBus::class) => 'command',
            str_starts_with($gatewayInterface, EventBus::class) => 'event',
            str_starts_with($gatewayInterface, QueryBus::class) => 'query',
            default => null,
        };

        return new self($messageKind, self::messageNameOf($message));
    }

    /**
     * @param self[] $causationChain
     */
    public static function describeChain(array $causationChain, Throwable $exception): string
    {
        $causations = array_values(array_filter($causationChain, fn (self $causation) => $causation->messageKind !== null));
        $failedMessage = array_pop($causations);
        if ($failedMessage === null || $causations === []) {
            return '';
        }

        $sender = self::userlandSenderOf($exception);
        $description = ucfirst($failedMessage->messageKind) . " {$failedMessage->messageName} was {$failedMessage->verb()}" . ($sender !== null ? " by {$sender}()" : '');

        $causingMessage = array_pop($causations);
        $description .= " while handling {$causingMessage->messageKind} {$causingMessage->messageName}";
        while (($earlierMessage = array_pop($causations)) !== null) {
            $description .= ", which was {$causingMessage->verb()} while handling {$earlierMessage->messageKind} {$earlierMessage->messageName}";
            $causingMessage = $earlierMessage;
        }

        return $description . '.';
    }

    private function verb(): string
    {
        return $this->messageKind === 'event' ? 'published' : 'sent';
    }

    private static function messageNameOf(Message $message): string
    {
        foreach ([MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME, MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME, MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME] as $routingHeader) {
            if ($message->getHeaders()->containsKey($routingHeader)) {
                return (string) $message->getHeaders()->get($routingHeader);
            }
        }

        $payload = $message->getPayload();

        return is_object($payload) ? $payload::class : get_debug_type($payload);
    }

    private static function userlandSenderOf(Throwable $exception): ?string
    {
        $trace = $exception->getTrace();
        foreach ($trace as $position => $frame) {
            if (! str_starts_with($frame['class'] ?? '', 'Ecotone\\__Proxy__\\')) {
                continue;
            }

            $caller = $trace[$position + 1] ?? null;
            if ($caller === null || ! isset($caller['class']) || str_starts_with($caller['class'], 'Ecotone\\')) {
                return null;
            }

            return $caller['class'] . '::' . $caller['function'];
        }

        return null;
    }
}

<?php

namespace Ecotone\Modelling\MessageHandling\MetadataPropagator;

use Closure;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\PropagateHeaders;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use Ecotone\Messaging\Execution\ExecutionScope;
use Ecotone\Messaging\Support\FrameworkHeaders;
use WeakMap;

/**
 * licence Apache-2.0
 */
class MessageHeadersPropagatorInterceptor
{
    public const GET_CURRENTLY_PROPAGATED_HEADERS_CHANNEL = 'ecotone.getCurrentlyPropagatedHeaders';
    public const ENABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.enablePollingConsumerPropagation';
    public const DISABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.disablePollingConsumerPropagation';
    public const IS_POLLING_CONSUMER_PROPAGATION_CONTEXT = 'ecotone.isPollingConsumerPropagation';
    private WeakMap $exceptionsWithCausationChain;

    public function __construct()
    {
        $this->exceptionsWithCausationChain = new WeakMap();
    }

    public function storeHeaders(MethodInvocation|Closure $methodInvocation, Message $message, ?PropagateHeaders $propagateHeaders = null)
    {
        if ($propagateHeaders !== null && ! $propagateHeaders->doPropagation) {
            $userlandHeaders = [];
        } else {
            $userlandHeaders = FrameworkHeaders::unsetHeadersNotPropagatedToFollowingMessages($message->getHeaders()->headers());
            $userlandHeaders[MessageHeaders::MESSAGE_ID] = $message->getHeaders()->getMessageId();
            $userlandHeaders[MessageHeaders::MESSAGE_CORRELATION_ID] = $message->getHeaders()->getCorrelationId();
        }

        $propagated = $this->propagatedHeaders();
        if ($propagated->headersOfHandledMessages === []) {
            $propagated->lastCoroutineStartedBeforeTheHandledMessage = ExecutionScope::lastStartedCoroutineId();
        }
        $propagated->headersOfHandledMessages[] = $userlandHeaders;
        $propagated->causationChain[] = MessageCausation::from($methodInvocation, $message);
        try {
            if ($methodInvocation instanceof MethodInvocation) {
                $reply = $methodInvocation->proceed();
            } else {
                $reply = $methodInvocation();
            }
        } catch (AggregateNotFoundException $exception) {
            throw $this->withCausationChain($exception);
        } finally {
            array_pop($propagated->headersOfHandledMessages);
            array_pop($propagated->causationChain);
        }

        return $reply;
    }

    private function withCausationChain(AggregateNotFoundException $exception): AggregateNotFoundException
    {
        $causationChain = $this->propagatedHeaders()->causationChain;
        if (count($causationChain) < 2 || isset($this->exceptionsWithCausationChain[$exception])) {
            return $exception;
        }

        $exceptionWithCausationChain = AggregateNotFoundException::createFromPreviousException(
            $exception->getMessage() . '. ' . MessageCausation::describeChain($causationChain, $exception),
            $exception
        );
        $this->exceptionsWithCausationChain[$exceptionWithCausationChain] = true;

        return $exceptionWithCausationChain;
    }

    public function propagateHeaders(array $headers): array
    {
        return FrameworkHeaders::propagateContextHeaders($this->getLastHeaders(), $headers);
    }

    /**
     * @return array<string, mixed>
     */
    #[InternalHandler(self::GET_CURRENTLY_PROPAGATED_HEADERS_CHANNEL)]
    public function getLastHeaders(): array
    {
        $headers = end($this->propagatedHeaders()->headersOfHandledMessages);

        if ($this->isCalledForFirstTime($headers)) {
            return [];
        }

        return $headers;
    }

    #[InternalHandler(self::ENABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function enablePollingConsumerPropagation(): void
    {
        $this->propagatedHeaders()->isPollingConsumer = true;
    }

    #[InternalHandler(self::DISABLE_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function disablePollingConsumerPropagation(): void
    {
        $this->propagatedHeaders()->isPollingConsumer = false;
    }

    #[InternalHandler(self::IS_POLLING_CONSUMER_PROPAGATION_CONTEXT)]
    public function isPollingConsumer(): bool
    {
        return $this->propagatedHeaders()->isPollingConsumer;
    }

    private function propagatedHeaders(): PropagatedHeaders
    {
        return ExecutionScope::stateOf($this, static fn () => new PropagatedHeaders());
    }

    private function isCalledForFirstTime($headers): bool
    {
        return $headers === false;
    }
}

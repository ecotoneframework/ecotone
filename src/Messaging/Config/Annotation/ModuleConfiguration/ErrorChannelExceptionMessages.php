<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class ErrorChannelExceptionMessages
{
    public static function delayedRetryOnInboundChannelAdapter(string $className, string $methodName): string
    {
        return "#[DelayedRetry] cannot be used on an Inbound Channel Adapter `{$className}::{$methodName}`. "
            . 'Inbound Channel Adapters consume from external systems (Kafka, AMQP, scheduled tasks) and have no source Message Channel for the framework to reschedule a delayed retry into. '
            . 'Use #[ErrorChannel] to capture the failure for later replay (e.g. from a Dead Letter), and optionally combine it with #[InstantRetry] for in-process retries before forwarding to the Error Channel.';
    }

    public static function errorChannelDirectlyOnAsyncHandlerMethod(string $endpointId): string
    {
        return "Asynchronous handler `{$endpointId}` has `#[ErrorChannel]` placed directly on the handler method — this has no effect on async handlers. "
            . "Pass it via the #[Asynchronous] attribute instead: `#[Asynchronous('channel', asynchronousExecution: [new ErrorChannel('...')])]` so the polling consumer routes failures correctly.";
    }

    public static function delayedRetryDirectlyOnAsyncHandlerMethod(string $endpointId): string
    {
        return "Asynchronous handler `{$endpointId}` has `#[DelayedRetry]` placed directly on the handler method — this has no effect on async handlers. "
            . "Pass it via the #[Asynchronous] attribute instead: `#[Asynchronous('channel', asynchronousExecution: [new DelayedRetry(...)])]` so the polling consumer applies the retry policy correctly.";
    }

    public static function errorChannelAndDelayedRetryMutuallyExclusiveOnHandler(string $endpointId): string
    {
        return "Handler `{$endpointId}` declares both #[ErrorChannel] and #[DelayedRetry] in #[Asynchronous] asynchronousExecution — these are mutually exclusive. "
            . 'Use #[ErrorChannel] to send failures to a channel you control, OR #[DelayedRetry] to have Ecotone manage the retry+dead-letter flow with a generated channel.';
    }

    public static function errorChannelAndDelayedRetryMutuallyExclusiveOnGateway(string $gatewayInterfaceFqn): string
    {
        return "Gateway `{$gatewayInterfaceFqn}` declares both #[ErrorChannel] and #[DelayedRetry] — these are mutually exclusive. "
            . 'Use #[ErrorChannel] to send failures to a channel you control, OR #[DelayedRetry] to have Ecotone manage the retry+dead-letter flow with a generated channel.';
    }

    public static function cannotReplyToDeadLetterMessage(string $messageId): string
    {
        return "Can not reply to message {$messageId}, as it does not contain `polledChannelName`, `inboundRequestChannel` or `routingSlip` header. "
            . 'Please add one of them, so Message can be routed back to the original channel.';
    }

    public static function cannotReplayToUnknownChannel(string $targetChannel): string
    {
        return "Can not replay dead letter messages to channel `{$targetChannel}`, as no channel or handler with that name exists. "
            . 'Pass the name of an existing message channel or of a handler input channel (e.g. the routing key of a #[CommandHandler] or the inputChannelName of an #[InternalHandler]), '
            . 'or omit the target channel to replay each message to the channel it was polled from.';
    }

    public static function cannotReplayToNonAsynchronousChannel(string $targetChannel): string
    {
        return "Can not replay dead letter messages to channel `{$targetChannel}`, as it is not an asynchronous (pollable) channel. The target channel is the queue the replayed message is sent through: once that queue is consumed, the message runs its original handler. "
            . 'Pass the name of one of the asynchronous channels of the service (e.g. the channel of an #[Asynchronous] handler, or a channel registered with SimpleMessageChannelBuilder::createQueueChannel()), '
            . 'or omit the target channel to replay each message to the channel it was polled from.';
    }

    public static function delayedRetryRequiresPolledChannelName(string $originalErrorMessage): string
    {
        return 'Failed to handle Error Message via Retry Configuration, as it does not contain information about origination channel from which it was polled. Original error message: '
            . $originalErrorMessage;
    }

    public static function instantRetryNotOnInboundChannelAdapter(string $className, string $methodName): string
    {
        return 'InstantRetry attribute can only be used on Inbound Channel Adapter methods (annotated with MessageConsumer e.g. #[KafkaConsumer], #[RabbitConsumer], #[Scheduled]). '
            . "'{$className}::{$methodName}' has none.";
    }

    public static function instantRetryRequiresEnterprise(): string
    {
        return 'Instant retry attribute is available only for Ecotone Enterprise. See https://docs.ecotone.tech/enterprise for details.';
    }

    public static function asynchronousExecutionRequiresEnterprise(string $endpointId): string
    {
        return "Endpoint annotations on #[Asynchronous] attribute for endpoint `{$endpointId}` require Ecotone Enterprise licence. See https://docs.ecotone.tech/enterprise for details.";
    }

    public static function gatewayErrorChannelRequiresEnterprise(string $interfaceFqn, string $methodName): string
    {
        return "Gateway {$interfaceFqn}::{$methodName} is marked with synchronous Error Channel. This functionality is available as part of Ecotone Enterprise. See https://docs.ecotone.tech/enterprise for details.";
    }

    public static function gatewayDelayedRetryRequiresEnterprise(string $interfaceFqn, string $methodName): string
    {
        return "Gateway {$interfaceFqn}::{$methodName} is marked with #[DelayedRetry]. This functionality is available as part of Ecotone Enterprise. See https://docs.ecotone.tech/enterprise for details.";
    }
}

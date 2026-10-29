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
        return 'Instant retry attribute is available only for Ecotone Enterprise.';
    }

    public static function asynchronousExecutionRequiresEnterprise(string $endpointId): string
    {
        return "Endpoint annotations on #[Asynchronous] attribute for endpoint `{$endpointId}` require Ecotone Enterprise licence.";
    }

    public static function gatewayErrorChannelRequiresEnterprise(string $interfaceFqn, string $methodName): string
    {
        return "Gateway {$interfaceFqn}::{$methodName} is marked with synchronous Error Channel. This functionality is available as part of Ecotone Enterprise.";
    }

    public static function gatewayDelayedRetryRequiresEnterprise(string $interfaceFqn, string $methodName): string
    {
        return "Gateway {$interfaceFqn}::{$methodName} is marked with #[DelayedRetry]. This functionality is available as part of Ecotone Enterprise.";
    }
}

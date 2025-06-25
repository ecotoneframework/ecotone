<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\PrecedenceChannelInterceptor;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;

/**
 * licence Apache-2.0
 */
final class RetriesChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    public function __construct(
        private string $relatedChannel,
        private RetryTemplate $retryTemplate,
        private ?string $errorChannel
    ) {
    }

    public function relatedChannelName(): string
    {
        return $this->relatedChannel;
    }

    public function getPrecedence(): int
    {
        return PrecedenceChannelInterceptor::DEFAULT_PRECEDENCE;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(SendRetryChannelInterceptor::class, [
            $this->relatedChannel,
            $this->retryTemplate,
            $this->errorChannel,
            Reference::to(ErrorChannelService::class),
            new Reference(ConfiguredMessagingSystem::class),
            new Reference(LoggingGateway::class),
            new Reference(EcotoneClockInterface::class),
        ]);
    }
}

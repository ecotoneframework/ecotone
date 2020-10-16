<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;
use Psr\Log\LogLevel;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;

/**
 * Class LoggingHandlerBuilder
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    const LOGGER_REFERENCE = "logger";
    const LOG_FULL_MESSAGE = false;

    private string $logLevel = LogLevel::DEBUG;
    private bool $logFullMessage = self::LOG_FULL_MESSAGE;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $methodParameterConverters = [];
    private bool $isBefore;

    /**
     * LoggingHandlerBuilder constructor.
     * @param bool $isBefore
     */
    private function __construct(bool $isBefore)
    {
        $this->isBefore = $isBefore;
        $this->methodParameterConverters[] = MessageConverterBuilder::create("message");
    }

    /**
     * @return LoggingHandlerBuilder
     */
    public static function createForBefore() : self
    {
        return new self(true);
    }

    public static function createForAfter() : self
    {
        return new self(false);
    }

    /**
     * @param string $logLevel
     * @return LoggingHandlerBuilder
     */
    public function withLogLevel(string $logLevel) : self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * @param bool $logFullMessage
     * @return LoggingHandlerBuilder
     */
    public function withLogFullMessage(bool $logFullMessage) : self
    {
        $this->logFullMessage = $logFullMessage;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        $this->methodParameterConverters = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverters;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return
            ServiceActivatorBuilder::createWithDirectReference(
                new LoggingInterceptor(
                    new LoggingService(
                        $referenceSearchService->get(ConversionService::REFERENCE_NAME),
                        $referenceSearchService->get(self::LOGGER_REFERENCE)
                    )
                ),
                $this->getMethodName()
            )
                ->withMethodParameterConverters($this->getParameterConverters())
                ->withPassThroughMessageOnVoidInterface(true)
                ->withOutputMessageChannel($this->outputMessageChannelName)
                ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [$interfaceToCallRegistry->getFor(LoggingInterceptor::class, $this->getMethodName())];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(LoggingInterceptor::class, $this->getMethodName());
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [self::LOGGER_REFERENCE];
    }

    /**
     * @return string
     */
    private function getMethodName(): string
    {
        return $this->isBefore ? "logBefore" : "logAfter";
    }
}
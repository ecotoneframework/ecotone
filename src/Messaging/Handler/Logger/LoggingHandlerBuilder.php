<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Logger;
use Psr\Log\LogLevel;
use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\InterceptorConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class LoggingHandlerBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    const LOGGER_REFERENCE = "logger";
    const LOG_FULL_MESSAGE = false;

    /**
     * @var string
     */
    private $logLevel = LogLevel::DEBUG;
    /**
     * @var bool
     */
    private $logFullMessage = self::LOG_FULL_MESSAGE;
    /**
     * @var ParameterConverterBuilder[]
     */
    private $methodParameterConverters = [];
    /**
     * @var bool
     */
    private $isBefore;

    /**
     * LoggingHandlerBuilder constructor.
     * @param bool $isBefore
     */
    private function __construct(bool $isBefore)
    {
        $this->isBefore = $isBefore;
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
    public function withMethodParameterConverters(array $methodParameterConverterBuilders)
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
            $this->isBefore ? "logBefore" : "logAfter"
            )
                ->withMethodParameterConverters($this->getParameterConverters())
                ->withPassThroughMessageOnVoidInterface(true)
                ->withOutputMessageChannel($this->outputMessageChannelName)
                ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [$interfaceToCallRegistry->getFor(LoggingInterceptor::class, "log")];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(LoggingInterceptor::class, "log");
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [self::LOGGER_REFERENCE];
    }
}
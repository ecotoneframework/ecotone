<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use Ecotone\Modelling\QueryBus;

/**
 * Class BusRouterBuilder
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BusRouterBuilder implements MessageHandlerBuilder
{
    private ?string $endpointId;

    private array $channelNamesRouting;
    /**
     * @var string[]
     */
    private string $inputChannelName;
    private string $type;
    private MessageHeadersPropagator $messageHeadersPropagator;

    /**
     * @param string[]  $channelNamesRouting
     *
     * @throws \Exception
     */
    private function __construct(MessageHeadersPropagator $messageHeadersPropagator, string $endpointId, string $inputChannelName, array $channelNamesRouting, string $type)
    {
        $this->channelNamesRouting = $channelNamesRouting;
        $this->inputChannelName = $inputChannelName;
        $this->type = $type;
        $this->endpointId = $endpointId;
        $this->messageHeadersPropagator = $messageHeadersPropagator;
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createEventBusByObject(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::EVENT_CHANNEL_NAME_BY_OBJECT . ".endpoint",
            BusModule::EVENT_CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "eventByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createEventBusByName(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::EVENT_CHANNEL_NAME_BY_NAME . ".endpoint",
            BusModule::EVENT_CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "eventByName"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createCommandBusByObject(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::COMMAND_CHANNEL_NAME_BY_OBJECT . ".endpoint",
            BusModule::COMMAND_CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "commandByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createCommandBusByName(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::COMMAND_CHANNEL_NAME_BY_NAME . ".endpoint",
            BusModule::COMMAND_CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "commandByName"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createQueryBusByObject(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::QUERY_CHANNEL_NAME_BY_OBJECT . ".endpoint",
            BusModule::QUERY_CHANNEL_NAME_BY_OBJECT,
            $channelNamesRouting,
            "queryByObject"
        );
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createQueryBusByName(MessageHeadersPropagator $messageHeadersPropagator, array $channelNamesRouting) : self
    {
        return new self(
            $messageHeadersPropagator,
            BusModule::QUERY_CHANNEL_NAME_BY_NAME . ".endpoint",
            BusModule::QUERY_CHANNEL_NAME_BY_NAME,
            $channelNamesRouting,
            "queryByName"
        );
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        switch ($this->type) {
            case "eventByObject": {
                return RouterBuilder::createRouterFromObject(
                    new EventBusRouter($this->channelNamesRouting),
                    "routeByObject"
                )   ->setResolutionRequired(false)
                    ->build($channelResolver, $referenceSearchService);
            }
            case "eventByName": {
                return RouterBuilder::createRouterFromObject(
                    new EventBusRouter($this->channelNamesRouting),
                    "routeByName"
                )
                    ->setResolutionRequired(false)
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("routedName", BusModule::EVENT_CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
            case "commandByObject": {
                return RouterBuilder::createRouterFromObject(
                    new CommandBusRouter($this->channelNamesRouting),
                    "routeByObject"
                )->build($channelResolver, $referenceSearchService);
            }
            case "commandByName": {
                return RouterBuilder::createRouterFromObject(
                    new CommandBusRouter($this->channelNamesRouting),
                    "routeByName"
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("name", BusModule::COMMAND_CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
            case "queryByObject": {
                return RouterBuilder::createRouterFromObject(
                    new QueryBusRouter($this->channelNamesRouting),
                    "routeByObject"
                )->build($channelResolver, $referenceSearchService);
            }
            case "queryByName": {
                return RouterBuilder::createRouterFromObject(
                    new QueryBusRouter($this->channelNamesRouting),
                    "routeByName"
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::createOptional("name", BusModule::QUERY_CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
        }

        throw InvalidArgumentException::create("Incorrect type {$this->type}");
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(QueryBusRouter::class, "routeByName"),
            $interfaceToCallRegistry->getFor(QueryBusRouter::class, "routeByObject"),
            $interfaceToCallRegistry->getFor(CommandBusRouter::class, "routeByName"),
            $interfaceToCallRegistry->getFor(CommandBusRouter::class, "routeByObject"),
            $interfaceToCallRegistry->getFor(EventBusRouter::class, "routeByName"),
            $interfaceToCallRegistry->getFor(EventBusRouter::class, "routeByObject"),
        ];
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $self = clone $this;
        $self->inputChannelName = $inputChannelName;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getEndpointId(): ?string
    {
        return $this->endpointId;
    }

    /**
     * @inheritDoc
     */
    public function withEndpointId(string $endpointId): void
    {
        $this->endpointId = $endpointId;
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public function __toString()
    {
        return BusRouterBuilder::class;
    }
}
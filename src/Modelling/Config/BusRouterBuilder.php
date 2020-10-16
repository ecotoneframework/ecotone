<?php

namespace Ecotone\Modelling\Config;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
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

    /**
     * BusRouterBuilder constructor.
     *
     * @param string $endpointId
     * @param string $inputChannelName
     * @param string[]  $channelNamesRouting
     * @param string $type
     *
     * @throws \Exception
     */
    private function __construct(string $endpointId, string $inputChannelName, array $channelNamesRouting, string $type)
    {
        $this->channelNamesRouting = $channelNamesRouting;
        $this->inputChannelName = $inputChannelName;
        $this->type = $type;
        $this->endpointId = $endpointId;
    }

    /**
     * @param string[] $channelNamesRouting
     *
     * @return BusRouterBuilder
     * @throws \Exception
     */
    public static function createEventBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            EventBus::CHANNEL_NAME_BY_OBJECT . ".endpoint",
            EventBus::CHANNEL_NAME_BY_OBJECT,
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
    public static function createEventBusByName(array $channelNamesRouting) : self
    {
        return new self(
            EventBus::CHANNEL_NAME_BY_NAME . ".endpoint",
            EventBus::CHANNEL_NAME_BY_NAME,
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
    public static function createCommandBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            CommandBus::CHANNEL_NAME_BY_OBJECT . ".endpoint",
            CommandBus::CHANNEL_NAME_BY_OBJECT,
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
    public static function createCommandBusByName(array $channelNamesRouting) : self
    {
        return new self(
            CommandBus::CHANNEL_NAME_BY_NAME . ".endpoint",
            CommandBus::CHANNEL_NAME_BY_NAME,
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
    public static function createQueryBusByObject(array $channelNamesRouting) : self
    {
        return new self(
            QueryBus::CHANNEL_NAME_BY_OBJECT . ".endpoint",
            QueryBus::CHANNEL_NAME_BY_OBJECT,
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
    public static function createQueryBusByName(array $channelNamesRouting) : self
    {
        return new self(
            QueryBus::CHANNEL_NAME_BY_NAME . ".endpoint",
            QueryBus::CHANNEL_NAME_BY_NAME,
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
                        HeaderBuilder::createOptional("name", EventBus::CHANNEL_NAME_BY_NAME)
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
                        HeaderBuilder::createOptional("name", CommandBus::CHANNEL_NAME_BY_NAME)
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
                        HeaderBuilder::createOptional("name", QueryBus::CHANNEL_NAME_BY_NAME)
                    ])
                    ->build($channelResolver, $referenceSearchService);
            }
        }
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
    public function withInputChannelName(string $inputChannelName)
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
    public function withEndpointId(string $endpointId)
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
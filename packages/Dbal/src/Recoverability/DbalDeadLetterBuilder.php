<?php


namespace Ecotone\Dbal\Recoverability;


use Ecotone\Dbal\DbalReconnectableConnectionFactory;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessageHeaders;

class DbalDeadLetterBuilder extends InputOutputMessageHandlerBuilder
{
    const LIMIT_HEADER  = "ecotone.dbal.deadletter.limit";
    const OFFSET_HEADER = "ecotone.dbal.deadletter.offset";

    const LIST_CHANNEL  = "ecotone.dbal.deadletter.list";
    const SHOW_CHANNEL       = "ecotone.dbal.deadletter.show";
    const REPLAY_CHANNEL     = "ecotone.dbal.deadletter.reply";
    const REPLAY_ALL_CHANNEL = "ecotone.dbal.deadletter.replyAll";
    const DELETE_CHANNEL     = "ecotone.dbal.deadletter.delete";
    const STORE_CHANNEL     = "dbal_dead_letter";

    private string $methodName;
    private string $connectionReferenceName;
    private array $parameterConverters;

    private function __construct(string $methodName, string $connectionReferenceName, string $inputChannelName, array $parameterConverters)
    {
        $this->methodName              = $methodName;
        $this->connectionReferenceName = $connectionReferenceName;
        $this->parameterConverters     = $parameterConverters;
        $this->inputMessageChannelName = $inputChannelName;
    }

    public static function createList(string $connectionReferenceName): self
    {
        return new self(
            "list", $connectionReferenceName, self::LIST_CHANNEL, [
            HeaderBuilder::create("limit", self::LIMIT_HEADER),
            HeaderBuilder::create("offset", self::OFFSET_HEADER)
        ]
        );
    }

    public static function createShow(string $connectionReferenceName): self
    {
        return new self("show", $connectionReferenceName, self::SHOW_CHANNEL, [
            PayloadBuilder::create("messageId"),
            HeaderBuilder::createOptional("replyChannel", MessageHeaders::REPLY_CHANNEL)
        ]);
    }

    public static function createReply(string $connectionReferenceName): self
    {
        return new self("reply", $connectionReferenceName, self::REPLAY_CHANNEL, []);
    }

    public static function createReplyAll(string $connectionReferenceName): self
    {
        return new self(
            "replyAll", $connectionReferenceName, self::REPLAY_ALL_CHANNEL, [
            ReferenceBuilder::create("messagingEntrypoint", MessagingEntrypoint::class)
        ]
        );
    }

    public static function createDelete(string $connectionReferenceName): self
    {
        return new self("delete", $connectionReferenceName, self::DELETE_CHANNEL, []);
    }

    public static function createStore(string $connectionReferenceName): self
    {
        return new self("store", $connectionReferenceName, self::STORE_CHANNEL, []);
    }

    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(DbalDeadLetter::class, $this->methodName);
    }

    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $messageHandler = ServiceActivatorBuilder::createWithDirectReference(
            new DbalDeadLetter(
                CachedConnectionFactory::createFor(new DbalReconnectableConnectionFactory($referenceSearchService->get($this->connectionReferenceName))),
                DefaultHeaderMapper::createAllHeadersMapping($referenceSearchService->get(ConversionService::REFERENCE_NAME))
            ),
            $this->methodName
        );

        foreach ($this->orderedAroundInterceptors as $orderedAroundInterceptor) {
            $messageHandler->addAroundInterceptor($orderedAroundInterceptor);
        }

        return $messageHandler
            ->withMethodParameterConverters($this->parameterConverters)
            ->withEndpointId($this->getEndpointId())
            ->withInputChannelName($this->getInputMessageChannelName())
            ->withOutputMessageChannel($this->getOutputMessageChannelName())
            ->build($channelResolver, $referenceSearchService);
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "list"),
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "show"),
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "reply"),
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "replyAll"),
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "delete"),
            $interfaceToCallRegistry->getFor(DbalDeadLetter::class, "store"),
        ];
    }

    public function getEndpointId(): ?string
    {
        return $this->getInputMessageChannelName() . ".endpoint";
    }

    public function withEndpointId(string $endpointId): self
    {
        return $this;
    }

    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}
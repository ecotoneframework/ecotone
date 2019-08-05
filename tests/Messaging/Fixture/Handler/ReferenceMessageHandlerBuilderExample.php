<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;
use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHandler;

/**
 * Class ModuleMessageHandlerBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceMessageHandlerBuilderExample extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var object
     */
    private $module;
    /**
     * @var string
     */
    private $moduleName;
    /**
     * @var string
     */
    private $channelName;

    /**
     * ModuleMessageHandlerBuilder constructor.
     * @param string $channelName
     * @param string $moduleName
     */
    private function __construct(string $channelName, string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->channelName = $channelName;
    }

    public static function create(string $channelName, string $referenceNameToRetrieve) : self
    {
        return new self($channelName, $referenceNameToRetrieve);
    }

    public function getModule()
    {
        return $this->module;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $this->module = $referenceSearchService->get($this->moduleName);

        return ReplyViaHeadersMessageHandler::create("some");
    }

    /**
     * @inheritDoc
     */
    public function withInputChannelName(string $inputChannelName): self
    {
        $this->channelName = $inputChannelName;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(ReplyViaHeadersMessageHandler::class, "handle");
    }

    /**
     * @inheritDoc
     */
    public function getInputMessageChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return "moduleMessagingHandler";
    }
}
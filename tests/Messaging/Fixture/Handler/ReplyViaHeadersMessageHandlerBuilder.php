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
 * Class ReplyViaHeadersMessageHandlerBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReplyViaHeadersMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    private $replyData;
    /**
     * @var string
     */
    private $inputChannelName;
    /**
     * @var bool
     */
    private $canAdd;

    /**
     * ReplyViaHeadersMessageHandlerBuilder constructor.
     * @param $replyData
     */
    private function __construct($replyData)
    {
        $this->replyData = $replyData;
    }

    /**
     * @param $replyData
     * @return ReplyViaHeadersMessageHandlerBuilder
     */
    public static function create($replyData) : self
    {
        return new self($replyData);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        return ReplyViaHeadersMessageHandler::create($this->replyData);
    }

    /**
     * @param bool $canAdd
     * @return ReplyViaHeadersMessageHandlerBuilder
     */
    public function shouldAddReplyDataToMessagePayload(bool $canAdd) : self
    {
        $this->canAdd = $canAdd;

        return $this;
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
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry) : iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}
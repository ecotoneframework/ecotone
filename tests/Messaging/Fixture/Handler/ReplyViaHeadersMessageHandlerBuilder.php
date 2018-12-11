<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class ReplyViaHeadersMessageHandlerBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler
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
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}
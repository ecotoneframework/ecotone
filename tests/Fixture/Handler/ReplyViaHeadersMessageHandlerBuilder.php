<?php
declare(strict_types=1);

namespace Fixture\Handler;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ReplyViaHeadersMessageHandlerBuilder
 * @package Fixture\Handler
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
    public function withInputChannelName(string $inputChannelName)
    {
        $this->inputChannelName = $inputChannelName;

        return $this;
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
}
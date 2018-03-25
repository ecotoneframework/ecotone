<?php
declare(strict_types=1);

namespace Fixture\Handler;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ModuleMessageHandlerBuilder
 * @package Fixture\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ModuleMessageHandlerBuilder implements MessageHandlerBuilder
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

    public static function create(string $channelName, string $moduleName) : self
    {
        return new self($channelName, $moduleName);
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
        $this->module = $referenceSearchService->findByReference($this->moduleName);

        return ReplyViaHeadersMessageHandler::create("some");
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
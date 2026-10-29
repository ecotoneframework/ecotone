<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;

/**
 * Class ModuleMessageHandlerBuilder
 * @package Test\Ecotone\Messaging\Fixture\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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

    public static function create(string $channelName, string $referenceNameToRetrieve): self
    {
        return new self($channelName, $referenceNameToRetrieve);
    }

    public function getModule()
    {
        return $this->module;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        // TODO: this seems useless
        return new Definition(ReplyViaHeadersMessageHandler::class, ['some'], 'create');
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
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(ReplyViaHeadersMessageHandler::class, 'handle');
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
    public function __toString()
    {
        return 'moduleMessagingHandler';
    }
}

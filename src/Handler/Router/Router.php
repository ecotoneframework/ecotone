<?php

namespace SimplyCodedSoftware\Messaging\Handler\Router;

use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class Router
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
final class Router implements MessageHandler
{
    /**
     * @var ChannelResolver
     */
    private $channelResolver;
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;
    /**
     * @var bool
     */
    private $isResolutionRequired;

    /**
     * RouterBuilder constructor.
     * @param ChannelResolver $channelResolver
     * @param MethodInvoker $methodInvoker
     * @param bool $isResolutionRequired
     */
    private function __construct(ChannelResolver $channelResolver, MethodInvoker $methodInvoker, bool $isResolutionRequired)
    {
        $this->channelResolver = $channelResolver;
        $this->methodInvoker = $methodInvoker;
        $this->isResolutionRequired = $isResolutionRequired;
    }

    /**
     * @param ChannelResolver $channelResolver
     * @param $objectToInvoke
     * @param string $methodName
     * @param bool $isResolutionRequired
     * @param array|MessageToParameterConverter[] $methodArguments
     * @return Router
     */
    public static function create(ChannelResolver $channelResolver, $objectToInvoke, string $methodName, bool $isResolutionRequired, array $methodArguments) : self
    {
        return new self($channelResolver, MethodInvoker::createWith($objectToInvoke, $methodName, $methodArguments), $isResolutionRequired);
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $resolutionChannels = $this->methodInvoker->processMessage($message);

        if (!is_array($resolutionChannels)) {
            $resolutionChannels = [$resolutionChannels];
        }

        if (empty($resolutionChannels) && $this->isResolutionRequired) {
            throw DestinationResolutionException::create("Can't resolve destination, because there are no channels to send message to.");
        }

        foreach ($resolutionChannels as $resolutionChannel) {
            $outputChannel = $this->channelResolver->resolve($resolutionChannel);

            $outputChannel->send($message);
        }
    }
}
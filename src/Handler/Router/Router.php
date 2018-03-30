<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Router;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class Router
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Router
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
     * @var null|string
     */
    private $defaultResolutionChannelName;

    /**
     * RouterBuilder constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param MethodInvoker   $methodInvoker
     * @param bool            $isResolutionRequired
     * @param null|string     $defaultResolutionChannelName
     */
    private function __construct(ChannelResolver $channelResolver, MethodInvoker $methodInvoker, bool $isResolutionRequired, ?string $defaultResolutionChannelName)
    {
        $this->channelResolver = $channelResolver;
        $this->methodInvoker = $methodInvoker;
        $this->isResolutionRequired = $isResolutionRequired;
        $this->defaultResolutionChannelName = $defaultResolutionChannelName;
    }

    /**
     * @param ChannelResolver                     $channelResolver
     * @param                                     $objectToInvoke
     * @param string                              $methodName
     * @param bool                                $isResolutionRequired
     * @param array|MessageToParameterConverter[] $methodArguments
     * @param null|string                         $defaultResolutionChannel
     *
     * @return Router
     */
    public static function create(ChannelResolver $channelResolver, $objectToInvoke, string $methodName, bool $isResolutionRequired, array $methodArguments, ?string $defaultResolutionChannel) : self
    {
        return new self($channelResolver, MethodInvoker::createWith($objectToInvoke, $methodName, $methodArguments), $isResolutionRequired, $defaultResolutionChannel);
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
        if (empty($resolutionChannels) && $this->defaultResolutionChannelName) {
            $resolutionChannels[] = $this->defaultResolutionChannelName;
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
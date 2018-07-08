<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Router;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageToParameterConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MethodInvoker;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

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
     * @var bool
     */
    private $applySequence;

    /**
     * RouterBuilder constructor.
     *
     * @param ChannelResolver $channelResolver
     * @param MethodInvoker   $methodInvoker
     * @param bool            $isResolutionRequired
     * @param null|string     $defaultResolutionChannelName
     * @param bool            $applySequence
     */
    private function __construct(ChannelResolver $channelResolver, MethodInvoker $methodInvoker, bool $isResolutionRequired, ?string $defaultResolutionChannelName, bool $applySequence)
    {
        $this->channelResolver = $channelResolver;
        $this->methodInvoker = $methodInvoker;
        $this->isResolutionRequired = $isResolutionRequired;
        $this->defaultResolutionChannelName = $defaultResolutionChannelName;
        $this->applySequence = $applySequence;
    }

    /**
     * @param ChannelResolver                     $channelResolver
     * @param                                     $objectToInvoke
     * @param string                              $methodName
     * @param bool                                $isResolutionRequired
     * @param array|MessageToParameterConverter[] $methodArguments
     * @param null|string                         $defaultResolutionChannel
     *
     * @param bool                                $applySequence
     *
     * @return Router
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function create(ChannelResolver $channelResolver, $objectToInvoke, string $methodName, bool $isResolutionRequired, array $methodArguments, ?string $defaultResolutionChannel, bool $applySequence) : self
    {
        return new self($channelResolver, MethodInvoker::createWith($objectToInvoke, $methodName, $methodArguments), $isResolutionRequired, $defaultResolutionChannel, $applySequence);
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

        $sequenceSize = count($resolutionChannels);
        $sequenceNumber = 1;
        foreach ($resolutionChannels as $resolutionChannel) {
            $outputChannel = $this->channelResolver->resolve($resolutionChannel);

            $messageToSend = $this->applySequence
                                ? MessageBuilder::fromMessage($message)
                                    ->setHeader(MessageHeaders::SEQUENCE_NUMBER, $sequenceNumber)
                                    ->setHeader(MessageHeaders::SEQUENCE_SIZE, $sequenceSize)
                                    ->build()
                                : $message;

            $outputChannel->send($messageToSend);
            $sequenceNumber++;
        }
    }
}
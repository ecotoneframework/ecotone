<?php

declare(strict_types=1);

namespace Ecotone\Amqp;

use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Enqueue\InboundMessageConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\InboundChannelAdapterEntrypoint;
use Ecotone\Messaging\Endpoint\PollingConsumer\ConnectionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Scheduling\TaskExecutor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Interop\Amqp\AmqpMessage;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements TaskExecutor
{
    /**
     * @var CachedConnectionFactory
     */
    private $connectionFactory;
    /**
     * @var InboundChannelAdapterEntrypoint
     */
    private $inboundAmqpGateway;
    /**
     * @var bool
     */
    private $declareOnStartup;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var string
     */
    private $amqpQueueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var bool
     */
    private $initialized = false;
    /**
     * @var InboundMessageConverter
     */
    private $inboundMessageConverter;
    /**
     * @var bool
     */
    private $isMessageChannel;

    public function __construct(
        CachedConnectionFactory $cachedConnectionFactory,
        InboundChannelAdapterEntrypoint $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        InboundMessageConverter $inboundMessageConverter,
        bool $isMessageChannel
    ) {
        $this->connectionFactory = $cachedConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->inboundMessageConverter = $inboundMessageConverter;
        $this->isMessageChannel = $isMessageChannel;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function execute(): void
    {
        $message = $this->getMessage();

        if (! $message) {
            return;
        }

        Assert::isSubclassOf($message, Message::class, 'Passed object to amqp inbound channel adapter is not a Message');
        $this->inboundAmqpGateway->executeEntrypoint($message);
    }

    /**
     * @return Message|null
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function getMessage(): ?Message
    {
        try {
            if (! $this->initialized) {
                $this->amqpAdmin->declareQueueWithBindings($this->getQueueName(), $this->connectionFactory->createContext());
                $this->initialized = true;
            }

            $consumer = $this->connectionFactory->getConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->getQueueName()));

            /** @var AmqpMessage $amqpMessage */
            $amqpMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

            if (! $amqpMessage) {
                return null;
            }

            $messageBuilder = $this->inboundMessageConverter->toMessage($amqpMessage, $consumer);
            if ($amqpMessage->getContentType()) {
                $messageBuilder = $messageBuilder->setContentType(MediaType::parseMediaType($amqpMessage->getContentType()));
            }

            return $messageBuilder->build();
        } catch (Throwable $exception) {
            if ($this->isMessageChannel) {
                throw $exception;
            }

            throw new ConnectionException('There was a problem during polling amqp channel', 0, $exception);
        }
    }

    private function getQueueName(): string
    {
        return $this->amqpQueueName;
    }
}

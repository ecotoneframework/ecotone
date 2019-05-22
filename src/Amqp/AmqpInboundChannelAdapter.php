<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Enqueue\AmqpLib\AmqpConsumer;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer as EnqueueConsumer;
use Interop\Queue\Message as EnqueueMessage;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Endpoint\AcknowledgementCallback;
use SimplyCodedSoftware\Messaging\Endpoint\EntrypointGateway;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageConverter\HeaderMapper;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements TaskExecutor, EntrypointGateway
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
    /**
     * @var EntrypointGateway
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
     * @var string
     */
    private $acknowledgeMode;
    /**
     * @var HeaderMapper
     */
    private $headerMapper;

    /**
     * InboundAmqpEnqueueGateway constructor.
     *
     * @param AmqpConnectionFactory               $amqpConnectionFactory
     * @param EntrypointGateway $inboundAmqpGateway
     * @param AmqpAdmin                           $amqpAdmin
     * @param bool                                $declareOnStartup
     * @param string                              $amqpQueueName
     * @param int                                 $receiveTimeoutInMilliseconds
     * @param string                              $acknowledgeMode
     * @param HeaderMapper                        $headerMapper
     */
    public function __construct(
        AmqpConnectionFactory $amqpConnectionFactory,
        EntrypointGateway $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        string $acknowledgeMode,
        HeaderMapper $headerMapper
    )
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->acknowledgeMode = $acknowledgeMode;
        $this->headerMapper = $headerMapper;
    }

    /**
     * @var bool
     */
    private $initialized = false;
    /**
     * @var AmqpConsumer
     */
    private $initializedConsumer;

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function execute(): void
    {
        $message = $this->getMessage();

        if (!$message) {
            return;
        }

        $this->executeEntrypoint($message);
    }

    /**
     * @inheritDoc
     */
    public function executeEntrypoint($message)
    {
        Assert::isSubclassOf($message, Message::class, "Passed object to amqp inbound channel adapter is not a Message");

        /** @var AcknowledgementCallback $amqpAcknowledgementCallback */
        $amqpAcknowledgementCallback = $message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE);
        try {
            $this->inboundAmqpGateway->executeEntrypoint($message);

            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->accept();
            }
        } catch (Throwable $e) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->requeue();
            }
            throw $e;
        }
    }

    /**
     * @return Message|null
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function getMessage() : ?Message
    {
        if (!$this->initialized) {
            $context = $this->amqpConnectionFactory->createContext();
            $this->amqpAdmin->declareQueueWithBindings($this->amqpQueueName, $context);
            $this->initialized = true;
        }

        $consumer = $this->getConsumer();

        $amqpMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

        if (!$amqpMessage) {
            return null;
        }

        return $this->toMessage($amqpMessage, $consumer);
    }

    /**
     * @inheritDoc
     */
    private function toMessage($source, EnqueueConsumer $consumer): Message
    {
        if (!($source instanceof AmqpMessage)) {
            return null;
        }

        $messageBuilder = MessageBuilder::withPayload($source->getBody())
            ->setMultipleHeaders($this->headerMapper->mapToMessageHeaders($source->getProperties()));

        if (in_array($this->acknowledgeMode, [AmqpAcknowledgementCallback::AUTO_ACK, AmqpAcknowledgementCallback::MANUAL_ACK])) {
            if ($this->acknowledgeMode == AmqpAcknowledgementCallback::AUTO_ACK) {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithAutoAck($consumer, $source);
            } else {
                $amqpAcknowledgeCallback = AmqpAcknowledgementCallback::createWithManualAck($consumer, $source);
            }

            $messageBuilder = $messageBuilder
                ->setHeader(AmqpHeader::HEADER_ACKNOWLEDGE, $amqpAcknowledgeCallback);
        }

        if ($source->getContentType()) {
            $messageBuilder = $messageBuilder->setContentType(MediaType::parseMediaType($source->getContentType()));
        }

        return $messageBuilder->build();
    }

    /**
     * @return \Interop\Amqp\AmqpConsumer
     */
    private function getConsumer() : \Interop\Amqp\AmqpConsumer
    {
        if ($this->initializedConsumer) {
            return $this->initializedConsumer;
        }

        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));
        $this->initializedConsumer = $consumer;

        return $consumer;
    }
}
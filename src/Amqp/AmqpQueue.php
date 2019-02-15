<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\Impl\AmqpQueue as EnqueueQueue;

/**
 * Class AmqpQueue
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpQueue
{
    private const DEFAULT_DURABILITY = true;

    /**
     * @var EnqueueQueue
     */
    private $enqueueQueue;
    /**
     * @var bool
     */
    private $withDurability = self::DEFAULT_DURABILITY;

    /**
     * AmqpQueue constructor.
     * @param string $queueName
     */
    private function __construct(string $queueName)
    {
        $this->enqueueQueue = new EnqueueQueue($queueName);
    }

    /**
     * @return string
     */
    public function getQueueName() : string
    {
        return $this->enqueueQueue->getQueueName();
    }

    /**
     * @param string $queueName
     * @return AmqpQueue
     */
    public static function createWith(string $queueName) : self
    {
        return new self($queueName);
    }

    /**
     * @return EnqueueQueue
     */
    public function toEnqueueQueue() : EnqueueQueue
    {
        $amqpQueue = clone $this->enqueueQueue;

        if ($this->withDurability) {
            $amqpQueue->addFlag(EnqueueQueue::FLAG_DURABLE);
        }

        return $amqpQueue;
    }

    /**
     * the queue will survive a broker restart
     *
     * @param bool $isDurable
     * @return AmqpQueue
     */
    public function withDurability(bool $isDurable) : self
    {
        $this->withDurability = $isDurable;

        return $this;
    }

    /**
     * used by only one connection and the queue will be deleted when that connection closes
     *
     * @return AmqpQueue
     */
    public function withExclusivity() : self
    {
        $this->enqueueQueue->addFlag(EnqueueQueue::FLAG_EXCLUSIVE);

        return $this;
    }

    /**
     * queue that has had at least one consumer is deleted when last consumer unsubscribes
     *
     * @return AmqpQueue
     */
    public function withAutoDeletion() : self
    {
        $this->enqueueQueue->addFlag(EnqueueQueue::FLAG_AUTODELETE);

        return $this;
    }

    /**
     * optional, used by plugins and broker-specific features such as message TTL, queue length limit, etc
     *
     * @param string $name
     * @param $value
     * @return AmqpQueue
     */
    public function withArgument(string $name, $value) : self
    {
        $this->enqueueQueue->setArgument($name, $value);

        return $this;
    }
}
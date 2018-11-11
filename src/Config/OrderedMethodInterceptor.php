<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;

/**
 * Class Interceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderedMethodInterceptor
{
    const DEFAULT_ORDER_WEIGHT = 1;

    /**
     * @var MessageHandlerBuilderWithOutputChannel
     */
    private $messageHandler;
    /**
     * @var int
     */
    private $orderWeight;

    /**
     * Interceptor constructor.
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $orderWeight
     */
    private function __construct(MessageHandlerBuilderWithOutputChannel $messageHandler, int $orderWeight)
    {
        $this->messageHandler = $messageHandler;
        $this->orderWeight = $orderWeight;
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $orderWeight
     * @return OrderedMethodInterceptor
     */
    public static function create(MessageHandlerBuilderWithOutputChannel $messageHandler, int $orderWeight)
    {
        return new self($messageHandler, $orderWeight);
    }

    /**
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function getMessageHandler(): MessageHandlerBuilderWithOutputChannel
    {
        return $this->messageHandler;
    }

    /**
     * @return int
     */
    public function getOrderWeight(): int
    {
        return $this->orderWeight;
    }
}
<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ChainMessageHandler
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainMessageHandler implements MessageHandler
{
    /**
     * @var ChainGateway[]
     */
    private $messageHandlerGateways;

    /**
     * ChainMessageHandler constructor.
     * @param ChainGateway[] $messageHandlerGateways
     */
    private function __construct(array $messageHandlerGateways)
    {
        $this->messageHandlerGateways = $messageHandlerGateways;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        foreach ($this->messageHandlerGateways as $messageHandlerGateway) {
            $message = $messageHandlerGateway->publish($message);
        }
    }
}
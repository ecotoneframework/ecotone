<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Chain;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class ChainForwarder
 * @package SimplyCodedSoftware\Messaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainForwarder
{
    /**
     * @var ChainGateway
     */
    private $chainGateway;

    /**
     * ChainForwarder constructor.
     * @param ChainGateway $chainGateway
     */
    public function __construct(ChainGateway $chainGateway)
    {
        $this->chainGateway = $chainGateway;
    }

    /**
     * @param Message $message
     * @return null|Message
     */
    public function forward(Message $message) : ?Message
    {
        return $this->chainGateway->execute($message);
    }
}
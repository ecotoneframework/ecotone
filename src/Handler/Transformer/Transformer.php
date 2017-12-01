<?php

namespace Messaging\Handler\Transformer;

use Messaging\Handler\InterfaceToCall;
use Messaging\Handler\Processor\MethodInvoker\MessageArgument;
use Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Messaging\Message;
use Messaging\MessageChannel;
use Messaging\MessageHandler;
use Messaging\Support\MessageBuilder;

/**
 * Class TransformerHandler
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Transformer implements MessageHandler
{
    /**
     * @var MessageChannel
     */
    private $outputChannel;
    /**
     * @var MethodInvoker
     */
    private $methodInvoker;

    /**
     * Transformer constructor.
     * @param MessageChannel $outputChannel
     * @param MethodInvoker $methodInvoker
     */
    public function __construct(MessageChannel $outputChannel, MethodInvoker $methodInvoker)
    {
        $this->outputChannel = $outputChannel;
        $this->methodInvoker = $methodInvoker;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $reply = $this->methodInvoker->processMessage($message);
        $replyBuilder = MessageBuilder::fromMessage($message);

        if (is_null($reply)) {
            return;
        }

        if (is_array($reply)) {
            if (is_array($message->getPayload())) {
                $reply = $replyBuilder
                            ->setPayload($reply)
                            ->build();
            }else {
                $reply = $replyBuilder
                    ->setMultipleHeaders($reply)
                    ->build();
            }
        }else if (!($reply instanceof Message)) {
            $reply = $replyBuilder
                        ->setPayload($reply)
                        ->build();
        }

        $this->outputChannel->send($reply);
    }
}
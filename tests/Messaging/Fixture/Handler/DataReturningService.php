<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\MessageBuilder;

class DataReturningService
{
    private $data;
    /**
     * @var bool
     */
    private $asAMessage;
    /**
     * @var array
     */
    private $headers;

    private function __construct($data, bool $asAMessage, array $headers)
    {
        $this->data = $data;
        $this->asAMessage = $asAMessage;
        $this->headers = $headers;
    }

    public static function createServiceActivator($dataToReturn): MessageHandler
    {
        return self::createServiceActivatorBuilder($dataToReturn)->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    public static function createServiceActivatorWithReturnMessage($payload, array $headers): MessageHandler
    {
        return self::createServiceActivatorBuilderWithReturnMessage($payload, $headers)->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    public static function createServiceActivatorBuilder($dataToReturn): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($dataToReturn, false, []), "handle");
    }

    public static function createServiceActivatorBuilderWithReturnMessage($payload, array $headers): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($payload, true, $headers), "handle");
    }

    public function handle(Message $message)
    {
        if ($this->asAMessage) {
            return MessageBuilder::fromMessage($message)
                        ->setMultipleHeaders($this->headers)
                        ->setPayload($this->data)
                        ->build();
        }

        return $this->data;
    }
}
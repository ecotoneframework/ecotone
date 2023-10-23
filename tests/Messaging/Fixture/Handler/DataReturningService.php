<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\DefinitionHelper;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use InvalidArgumentException;
use Throwable;

class DataReturningService implements DefinedObject
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

    private ?Throwable $exception;

    public function __construct($data, bool $asAMessage, array $headers, ?Throwable $exception)
    {
        $this->data = $data;
        $this->asAMessage = $asAMessage;
        $this->headers = $headers;
        $this->exception = $exception;
    }

    public static function createServiceActivator($dataToReturn): MessageHandler
    {
        return ComponentTestBuilder::create()->build(self::createServiceActivatorBuilder($dataToReturn));
    }

    public static function createServiceActivatorWithReturnMessage($payload, array $headers): MessageHandler
    {
        return ComponentTestBuilder::create()->build(self::createServiceActivatorBuilderWithReturnMessage($payload, $headers));
    }

    public static function createServiceActivatorBuilder($dataToReturn): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($dataToReturn, false, [], null), 'handle');
    }

    public static function createExceptionalServiceActivatorBuilder(): ServiceActivatorBuilder
    {
        return (ServiceActivatorBuilder::createWithDirectReference(new self('', false, [], new InvalidArgumentException('error during handling')), 'handle'));
    }

    public static function createServiceActivatorBuilderWithReturnMessage($payload, array $headers): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($payload, true, $headers, null), 'handle');
    }

    public static function createServiceActivatorBuilderWithRejectException(): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self('', true, [], new RejectMessageException('rejecting message')), 'handle');
    }

    public function handle(Message $message)
    {
        if ($this->exception) {
            throw new $this->exception();
        }

        if ($this->asAMessage) {
            return MessageBuilder::fromMessage($message)
                        ->setMultipleHeaders($this->headers)
                        ->setPayload($this->data)
                        ->build();
        }

        return $this->data;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->data,
            $this->asAMessage,
            $this->headers,
            $this->exception ? DefinitionHelper::buildDefinitionFromInstance($this->exception) : null,
        ]);
    }
}

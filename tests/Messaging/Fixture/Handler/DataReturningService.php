<?php

namespace Test\Ecotone\Messaging\Fixture\Handler;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use InvalidArgumentException;

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

    private bool $exception;
    private bool $rejectException;

    public function __construct($data, bool $asAMessage, array $headers, bool $exception = false, bool $rejectException = false)
    {
        $this->data = $data;
        $this->asAMessage = $asAMessage;
        $this->headers = $headers;
        $this->exception = $exception;
        $this->rejectException = $rejectException;
    }

    public static function createServiceActivator($dataToReturn): MessageHandler
    {
        return ComponentTestBuilder::create()->build(self::createServiceActivatorBuilder($dataToReturn));
    }

    public static function createServiceActivatorWithReturnMessage($payload, array $headers): MessageHandler
    {
        return ComponentTestBuilder::create()->build(self::createServiceActivatorBuilderWithReturnMessage($payload, $headers));
    }

    public static function createServiceActivatorWithGenerator(array $payload): MessageHandler
    {
        return ComponentTestBuilder::create()->build(
            ServiceActivatorBuilder::createWithDirectReference(new self($payload, false, [], false), 'iterate')
        );
    }

    public static function createServiceActivatorBuilder($dataToReturn): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($dataToReturn, false, [], false), 'handle');
    }

    public static function createExceptionalServiceActivatorBuilder(): ServiceActivatorBuilder
    {
        return (ServiceActivatorBuilder::createWithDirectReference(new self('', false, [], true), 'handle'));
    }

    public static function createServiceActivatorBuilderWithReturnMessage($payload, array $headers): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self($payload, true, $headers, false), 'handle');
    }

    public static function createServiceActivatorBuilderWithRejectException(): ServiceActivatorBuilder
    {
        return ServiceActivatorBuilder::createWithDirectReference(new self('', true, [], true, true), 'handle');
    }

    public function handle(Message $message)
    {
        if ($this->exception) {
            if ($this->rejectException) {
                throw new RejectMessageException('rejecting message');
            }

            throw new InvalidArgumentException('error during handling');
        }

        if ($this->asAMessage) {
            return MessageBuilder::fromMessage($message)
                        ->setMultipleHeaders($this->headers)
                        ->setPayload($this->data)
                        ->build();
        }

        return $this->data;
    }

    public function iterate()
    {
        foreach ($this->data as $item) {
            yield $item;
        }
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->data,
            $this->asAMessage,
            $this->headers,
            $this->exception,
            $this->rejectException,
        ]);
    }
}

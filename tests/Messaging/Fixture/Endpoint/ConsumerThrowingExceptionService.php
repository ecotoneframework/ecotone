<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Endpoint;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use RuntimeException;

/**
 * Class InboundChannelAdapterStoppingService
 * @package Test\Ecotone\Messaging\Fixture\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerThrowingExceptionService implements DefinedObject
{
    /**
     * @var ConsumerLifecycle
     */
    private $consumerLifecycle;

    private $called = 0;

    /**
     * InboundChannelAdapterStoppingService constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    public function execute(): void
    {
        $this->called++;
        if ($this->consumerLifecycle) {
            $this->consumerLifecycle->stop();
        }

        throw new RuntimeException('Test error. This should be caught');
    }

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     */
    public function setConsumerLifecycle(ConsumerLifecycle $consumerLifecycle): void
    {
        $this->consumerLifecycle = $consumerLifecycle;
    }

    public function getCalled(): int
    {
        return $this->called;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}

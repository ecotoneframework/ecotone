<?php

namespace Messaging\Handler\ServiceActivator;

use Messaging\MessagingRegistry;

/**
 * Class ServiceActivatorFactory
 * @package Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorFactory
{
    /**
     * @var MessagingRegistry
     */
    private $messagingRegistry;

    /**
     * ServiceActivatorFactory constructor.
     * @param MessagingRegistry $messagingRegistry
     */
    public function __construct(MessagingRegistry $messagingRegistry)
    {
        $this->messagingRegistry = $messagingRegistry;
    }

    /**
     * @param CreateServiceActivatorCommand $createServiceActivatorCommand
     * @return ServiceActivatingHandler
     */
    public function create(CreateServiceActivatorCommand $createServiceActivatorCommand) : ServiceActivatingHandler
    {
        return new ServiceActivatingHandler(
            new RequestReplyProducer(
                $createServiceActivatorCommand->getOutputChannel(),
                $createServiceActivatorCommand->isReplyRequired()
            ),
            MethodInvoker::createWith(
                $createServiceActivatorCommand->objectToInvokeOn(),
                $createServiceActivatorCommand->objectMethodName(),
                $createServiceActivatorCommand->getMethodArguments()
            ),
            $this->messagingRegistry
        );
    }
}
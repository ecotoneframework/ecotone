<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class Interceptor
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptor
{
    const DEFAULT_PRECEDENCE = 1;

    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var MessageHandlerBuilderWithOutputChannel
     */
    private $messageHandler;
    /**
     * @var int
     */
    private $precedence;
    /**
     * @var Pointcut
     */
    private $pointcut;

    /**
     * Interceptor constructor.
     * @param string $interceptorName
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $precedence
     * @param Pointcut $pointcut
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(string $interceptorName, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, Pointcut $pointcut)
    {
        $this->messageHandler = $messageHandler;
        $this->precedence = $precedence;
        $this->pointcut = $pointcut;
        $this->referenceName = $interceptorName;
    }

    /**
     * @param string $interceptorName
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @param int $precedence
     * @param string $pointcut
     * @return MethodInterceptor
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function create(string $interceptorName, MessageHandlerBuilderWithOutputChannel $messageHandler, int $precedence, string $pointcut)
    {
        return new self($interceptorName, $messageHandler, $precedence, Pointcut::createWith($pointcut));
    }

    /**
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function getMessageHandler(): MessageHandlerBuilderWithOutputChannel
    {
        return $this->messageHandler;
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @return bool
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function doesItCutWith(InterfaceToCallRegistry $interfaceToCallRegistry, MessageHandlerBuilderWithOutputChannel $messageHandler) : bool
    {
        return $this->pointcut->doesItCut($messageHandler->getInterceptedInterface($interfaceToCallRegistry), $messageHandler->getEndpointAnnotations());
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->getMessageHandler()}";
    }
}
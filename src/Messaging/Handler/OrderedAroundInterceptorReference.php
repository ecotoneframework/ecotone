<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundMethodInterceptor;

/**
 * Class InterceptorReference
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderedAroundInterceptorReference
{
    /**
     * @var int
     */
    private $precedence;
    /**
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;

    /**
     * InterceptorReference constructor.
     * @param int $precedence
     * @param string $referenceName
     * @param string $methodName
     */
    private function __construct(int $precedence, string $referenceName, string $methodName)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
        $this->precedence = $precedence;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return OrderedAroundInterceptorReference
     */
    public static function create(string $referenceName, string $methodName) : self
    {
        return new self(0, $referenceName, $methodName);
    }

    /**
     * @param int $precedence
     * @param string $referenceName
     * @param string $methodName
     * @return OrderedAroundInterceptorReference
     */
    public static function createWithPrecedence(int $precedence, string $referenceName, string $methodName) : self
    {
        return new self($precedence, $referenceName, $methodName);
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return AroundMethodInterceptor
     * @throws ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService) : AroundMethodInterceptor
    {
        /** @var InterfaceToCallRegistry $interfaceRegistry */
        $interfaceRegistry = $referenceSearchService->get(InterfaceToCallRegistry::REFERENCE_NAME);

        return AroundMethodInterceptor::createWith(
            $referenceSearchService->get($this->referenceName), $this->methodName,
            $interfaceRegistry
        );
    }
}
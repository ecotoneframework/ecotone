<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class InterceptorReference
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundInterceptorReference
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
     * @var Pointcut
     */
    private $pointcut;
    /**
     * @var object
     */
    private $directObject;

    /**
     * InterceptorReference constructor.
     * @param int $precedence
     * @param string $referenceName
     * @param string $methodName
     * @param Pointcut $pointcut
     */
    private function __construct(int $precedence, string $referenceName, string $methodName, Pointcut $pointcut)
    {
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
        $this->precedence = $precedence;
        $this->pointcut = $pointcut;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return AroundInterceptorReference
     */
    public static function createWithNoPointcut(string $referenceName, string $methodName) : self
    {
        return new self(MethodInterceptor::DEFAULT_PRECEDENCE, $referenceName, $methodName, Pointcut::createEmpty());
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     */
    public static function create(string $referenceName, string $methodName, int $precedence, string $pointcut) : self
    {
        return new self($precedence, $referenceName, $methodName, $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty());
    }

    /**
     * @param object $referenceObject
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithDirectObject($referenceObject, string $methodName, int $precedence, string $pointcut) : self
    {
        Assert::isObject($referenceObject, "Direct object for interceptor must be instance");
        $aroundInterceptorReference = new self($precedence, "", $methodName, Pointcut::createWith($pointcut));
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $interceptorsReferences
     * @return MethodInterceptor[]
     */
    public static function createAroundInterceptors(ReferenceSearchService $referenceSearchService, array $interceptorsReferences): array
    {
        $aroundMethodInterceptors = [];
        usort($interceptorsReferences, function (AroundInterceptorReference $element, AroundInterceptorReference $elementToCompare) {
            if ($element->getPrecedence() == $elementToCompare->getPrecedence()) {
                return 0;
            }

            return $element->getPrecedence() > $elementToCompare->getPrecedence() ? 1 : -1;
        });
        if ($interceptorsReferences) {
            foreach ($interceptorsReferences as $interceptorsReferenceName) {
                $interceptingService = $interceptorsReferenceName->buildAroundInterceptor($referenceSearchService);

                $aroundMethodInterceptors[] = $interceptingService;
            }
        }

        return $aroundMethodInterceptors;
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
    public function getReferenceName() : string
    {
        return $this->referenceName;
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
        return $this->pointcut->doesItCut($messageHandler->getInterceptedInterface($interfaceToCallRegistry));
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return AroundMethodInterceptor
     * @throws ReferenceNotFoundException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService) : AroundMethodInterceptor
    {
        return AroundMethodInterceptor::createWith(
            $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName),
            $this->methodName,
            $referenceSearchService
        );
    }
}
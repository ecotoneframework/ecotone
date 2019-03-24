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
    private $interceptorName;
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
     * @param string $interceptorName
     * @param string $methodName
     * @param Pointcut $pointcut
     */
    private function __construct(int $precedence, string $interceptorName, string $methodName, Pointcut $pointcut)
    {
        $this->interceptorName = $interceptorName;
        $this->methodName = $methodName;
        $this->precedence = $precedence;
        $this->pointcut = $pointcut;
    }

    /**
     * @param string $interceptorName
     * @param string $methodName
     * @return AroundInterceptorReference
     */
    public static function createWithNoPointcut(string $interceptorName, string $methodName) : self
    {
        return new self(MethodInterceptor::DEFAULT_PRECEDENCE, $interceptorName, $methodName, Pointcut::createEmpty());
    }

    /**
     * @param string $interceptorName
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     */
    public static function create(string $interceptorName, string $methodName, int $precedence, string $pointcut) : self
    {
        return new self($precedence, $interceptorName, $methodName, $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty());
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
     * @param AroundInterceptorReference[] $interceptorsReferences
     * @return MethodInterceptor[]
     * @throws ReferenceNotFoundException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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
     * For Around interceptor, name is also a reference name
     *
     * @return string
     */
    public function getInterceptorName() : string
    {
        return $this->interceptorName;
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
     * @param ReferenceSearchService $referenceSearchService
     * @return AroundMethodInterceptor
     * @throws ReferenceNotFoundException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService) : AroundMethodInterceptor
    {
        return AroundMethodInterceptor::createWith(
            $this->directObject ? $this->directObject : $referenceSearchService->get($this->interceptorName),
            $this->methodName,
            $referenceSearchService
        );
    }
}
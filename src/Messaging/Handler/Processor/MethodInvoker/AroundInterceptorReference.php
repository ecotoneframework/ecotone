<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use ReflectionException;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class InterceptorReference
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundInterceptorReference implements InterceptorWithPointCut
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
     * @var string
     */
    private $referenceName;
    /**
     * @var bool
     */
    private $allowForMethodArgumentReplacement = true;
    /**
     * @var string
     */
    private $argumentReplacementExceptionMessage = "";

    /**
     * InterceptorReference constructor.
     * @param int $precedence
     * @param string $interceptorName
     * @param string $referenceName
     * @param string $methodName
     * @param Pointcut $pointcut
     */
    private function __construct(int $precedence, string $interceptorName, string $referenceName, string $methodName, Pointcut $pointcut)
    {
        $this->interceptorName = $interceptorName;
        $this->methodName = $methodName;
        $this->precedence = $precedence;
        $this->pointcut = $pointcut;
        $this->referenceName = $referenceName;
    }

    /**
     * @param string $interceptorName
     * @param string $referenceName
     * @param string $methodName
     * @return AroundInterceptorReference
     */
    public static function createWithNoPointcut(string $interceptorName, string $referenceName, string $methodName): self
    {
        return new self(MethodInterceptor::DEFAULT_PRECEDENCE, $interceptorName, $referenceName, $methodName, Pointcut::createEmpty());
    }

    /**
     * @param string $interceptorName
     * @param string $referenceName
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     */
    public static function create(string $interceptorName, string $referenceName, string $methodName, int $precedence, string $pointcut): self
    {
        return new self($precedence, $interceptorName, $referenceName, $methodName, $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty());
    }

    /**
     * @param string $interceptorName
     * @param object $referenceObject
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     * @throws MessagingException
     */
    public static function createWithDirectObject(string $interceptorName, $referenceObject, string $methodName, int $precedence, string $pointcut): self
    {
        Assert::isObject($referenceObject, "Direct object for interceptor must be instance");
        $aroundInterceptorReference = new self($precedence, $interceptorName, "", $methodName, Pointcut::createWith($pointcut));
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param AroundInterceptorReference[] $interceptorsReferences
     * @return MethodInterceptor[]
     * @throws ReferenceNotFoundException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     * @throws InvalidArgumentException
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
     * @param ReferenceSearchService $referenceSearchService
     * @return AroundMethodInterceptor
     * @throws ReferenceNotFoundException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     * @throws InvalidArgumentException
     */
    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService): AroundMethodInterceptor
    {
        return AroundMethodInterceptor::createWith(
            $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName),
            $this->methodName,
            $referenceSearchService,
            $this->allowForMethodArgumentReplacement,
            $this->argumentReplacementExceptionMessage
        );
    }

    /**
     * @param string $errorMessage
     * @return AroundInterceptorReference
     */
    public function disallowMethodArgumentReplacement(string $errorMessage) : self
    {
        $copy = clone $this;
        $copy->allowForMethodArgumentReplacement = false;
        $copy->argumentReplacementExceptionMessage = $errorMessage;

        return $copy;
    }

    /**
     * For Around interceptor, name is also a reference name
     *
     * @return string
     */
    public function getInterceptorName(): string
    {
        return $this->interceptorName;
    }

    /**
     * @return string
     */
    public function getReferenceName() : string
    {
        return $this->referenceName;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingObject()
    {
        return $this;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param object[] $endpointAnnotations
     * @return bool
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations): bool
    {
        return $this->pointcut->doesItCut($interfaceToCall, $endpointAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function hasName(string $name): bool
    {
        return $this->interceptorName === $name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->interceptorName . $this->referenceName . $this->methodName;
    }
}
<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use ReflectionException;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class InterceptorReference
 * @package Ecotone\Messaging\Handler
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
    private $referenceName = "";

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
     */
    public static function createWithDirectObject(string $interceptorName, object $referenceObject, string $methodName, int $precedence, string $pointcut): self
    {
        $aroundInterceptorReference = new self($precedence, $interceptorName, "", $methodName, Pointcut::createWith($pointcut));
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
    }

    /**
     * @param string $interceptorName
     * @param AroundInterceptorObjectBuilder $aroundInterceptorObjectBuilder
     * @param string $methodName
     * @param int $precedence
     * @param string $pointcut
     * @return AroundInterceptorReference
     * @throws MessagingException
     */
    public static function createWithObjectBuilder(string $interceptorName, AroundInterceptorObjectBuilder $aroundInterceptorObjectBuilder, string $methodName, int $precedence, string $pointcut) : self
    {
        return self::createWithDirectObject($interceptorName, $aroundInterceptorObjectBuilder, $methodName, $precedence, $pointcut);
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     * @return InterfaceToCall
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     */
    public function getInterceptingInterface(InterfaceToCallRegistry $interfaceToCallRegistry) : InterfaceToCall
    {
        if ($this->directObject instanceof AroundInterceptorObjectBuilder) {
            return $interfaceToCallRegistry->getFor($this->directObject->getInterceptingInterfaceClassName(), $this->methodName);
        }

        if ($this->directObject) {
            return $interfaceToCallRegistry->getFor($this->directObject, $this->methodName);
        }

        return $interfaceToCallRegistry->getForReferenceName($this->referenceName, $this->methodName);
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public static function createAroundInterceptorsWithChannel(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $interceptorsReferences): array
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
                $interceptingService = $interceptorsReferenceName->buildAroundInterceptor($channelResolver, $referenceSearchService);

                $aroundMethodInterceptors[] = $interceptingService;
            }
        }

        return $aroundMethodInterceptors;
    }

    public function buildAroundInterceptor(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): AroundMethodInterceptor
    {
        $referenceToCall = $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
        if ($referenceToCall instanceof AroundInterceptorObjectBuilder) {
            $referenceToCall = $referenceToCall->build($channelResolver, $referenceSearchService);
        }

        return AroundMethodInterceptor::createWith(
            $referenceToCall,
            $this->methodName,
            $referenceSearchService
        );
    }

    /**
     * @return string
     */
    public function getInterceptorName(): string
    {
        return $this->interceptorName;
    }

    /**
     * @return array
     */
    public function getRequiredReferenceNames() : array
    {
        return $this->directObject instanceof AroundInterceptorObjectBuilder ? $this->directObject->getRequiredReferenceNames() : [$this->referenceName];
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
    public function addInterceptedInterfaceToCall(InterfaceToCall $interceptedInterface, array $endpointAnnotations)
    {
        return $this;
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
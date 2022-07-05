<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Handler\UnionTypeDescriptor;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Support\InvalidArgumentException;
use ReflectionException;

final class AroundInterceptorReference implements InterceptorWithPointCut
{
    private int $precedence;
    private string $interceptorName;
    private string $methodName;
    private Pointcut $pointcut;
    private ?object $directObject = null;
    private string $referenceName = "";
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters;

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    private function __construct(int $precedence, string $interceptorName, string $referenceName, string $methodName, Pointcut $pointcut, array $parameterConverters)
    {
        $this->interceptorName = $interceptorName;
        $this->methodName      = $methodName;
        $this->precedence      = $precedence;
        $this->pointcut        = $this->initializePointcut($interceptorName, $methodName, $pointcut, $parameterConverters);
        $this->referenceName   = $referenceName;
        $this->parameterConverters = $parameterConverters;
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    private function initializePointcut(string $interceptorClass, string $methodName, Pointcut $pointcut, array $parameterConverters) : Pointcut
    {
        if (!$pointcut->isEmpty()) {
            return $pointcut;
        }

        $interfaceToCall = InterfaceToCall::create($interceptorClass, $methodName);

        return Pointcut::initializeFrom($interfaceToCall, $parameterConverters);
    }

    public static function createWithNoPointcut(string $interceptorClass, string $referenceName, string $methodName): self
    {
        return new self(Precedence::DEFAULT_PRECEDENCE, $interceptorClass, $referenceName, $methodName, Pointcut::createEmpty(), []);
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    public static function create(string $interceptorClass, string $referenceName, string $methodName, int $precedence, string $pointcut, array $parameterConverters): self
    {
        return new self($precedence, $interceptorClass, $referenceName, $methodName, $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty(), $parameterConverters);
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    public static function createWithDirectObjectAndResolveConverters(object $referenceObject, string $methodName, int $precedence, string $pointcut): self
    {
        $parameterAnnotationResolver = ParameterConverterAnnotationFactory::create();
        $parameterConverters = $parameterAnnotationResolver->createParameterConverters(InterfaceToCall::create($referenceObject, $methodName));

        $aroundInterceptorReference               = new self($precedence, get_class($referenceObject), "", $methodName, Pointcut::createWith($pointcut), $parameterConverters);
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
    }

    /**
     * @param self[] $interceptorsReferences
     *
     * @return AroundMethodInterceptor[]
     */
    public static function createAroundInterceptorsWithChannel(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService, array $interceptorsReferences): array
    {
        $aroundMethodInterceptors = [];
        usort(
            $interceptorsReferences, function (AroundInterceptorReference $element, AroundInterceptorReference $elementToCompare) {
            if ($element->getPrecedence() == $elementToCompare->getPrecedence()) {
                return 0;
            }

            return $element->getPrecedence() > $elementToCompare->getPrecedence() ? 1 : -1;
        }
        );
        if ($interceptorsReferences) {
            foreach ($interceptorsReferences as $interceptorsReferenceName) {
                $interceptingService = $interceptorsReferenceName->buildAroundInterceptor($referenceSearchService);

                $aroundMethodInterceptors[] = $interceptingService;
            }
        }

        return $aroundMethodInterceptors;
    }

    /**
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     *
     * @return InterfaceToCall
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     * @throws ConfigurationException
     */
    public function getInterceptingInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
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

    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService): AroundMethodInterceptor
    {
        $referenceToCall = $this->directObject ?: $referenceSearchService->get($this->referenceName);

        $builtConverters = [];
        foreach ($this->parameterConverters as $parameterConverter) {
            $builtConverters[] = $parameterConverter->build($referenceSearchService);
        }

        return AroundMethodInterceptor::createWith(
            $referenceToCall,
            $this->methodName,
            $referenceSearchService,
            $builtConverters
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
    public function getRequiredReferenceNames(): array
    {
        return [$this->referenceName];
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingObject(): object
    {
        return $this;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param object[]        $endpointAnnotations
     *
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
    public function addInterceptedInterfaceToCall(InterfaceToCall $interceptedInterface, array $endpointAnnotations): self
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
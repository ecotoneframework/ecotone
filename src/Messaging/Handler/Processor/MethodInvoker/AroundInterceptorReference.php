<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MethodInvocationConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MethodInvocationObjectConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use InvalidArgumentException;

final class AroundInterceptorReference implements InterceptorWithPointCut
{
    private int $precedence;
    private string $interceptorName;
    private Pointcut $pointcut;
    private ?object $directObject = null;
    private string $referenceName = '';
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters;

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    private function __construct(int $precedence, string $referenceName, private InterfaceToCall $interfaceToCall, Pointcut $pointcut, array $parameterConverters)
    {
        $this->interceptorName = $this->interfaceToCall->getInterfaceName();
        $this->precedence      = $precedence;
        $this->pointcut        = $this->initializePointcut($interfaceToCall, $pointcut, $parameterConverters);
        $this->referenceName   = $referenceName;
        $this->parameterConverters = $parameterConverters;
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    private function initializePointcut(InterfaceToCall $interfaceToCall, Pointcut $pointcut, array $parameterConverters): Pointcut
    {
        if (! $pointcut->isEmpty()) {
            return $pointcut;
        }

        return Pointcut::initializeFrom($interfaceToCall, $parameterConverters);
    }

    public static function createWithNoPointcut(string $referenceName, InterfaceToCall $interfaceToCall): self
    {
        return new self(Precedence::DEFAULT_PRECEDENCE, $referenceName, $interfaceToCall, Pointcut::createEmpty(), []);
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    public static function create(string $referenceName, InterfaceToCall $interfaceToCall, int $precedence, string $pointcut, array $parameterConverters): self
    {
        return new self($precedence, $referenceName, $interfaceToCall, $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty(), $parameterConverters);
    }

    /**
     * @var ParameterConverterBuilder[] $parameterConverters
     */
    public static function createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry $interfaceToCallRegistry, object $referenceObject, string $methodName, int $precedence, string $pointcut): self
    {
        $parameterAnnotationResolver = ParameterConverterAnnotationFactory::create();
        $interfaceToCall = $interfaceToCallRegistry->getFor($referenceObject, $methodName);
        $parameterConverters = $parameterAnnotationResolver->createParameterConverters($interfaceToCall);

        $aroundInterceptorReference               = new self($precedence, '', $interfaceToCall, Pointcut::createWith($pointcut), $parameterConverters);
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
    }

    /**
     * @param self[] $interceptorsReferences
     *
     * @return AroundMethodInterceptor[]
     */
    public static function createAroundInterceptorsWithChannel(ReferenceSearchService $referenceSearchService, array $interceptorsReferences, array $endpointAnnotations, InterfaceToCall $interceptedInterface): array
    {
        usort(
            $interceptorsReferences,
            function (AroundInterceptorReference $element, AroundInterceptorReference $elementToCompare) {
                if ($element->getPrecedence() == $elementToCompare->getPrecedence()) {
                    return 0;
                }

                return $element->getPrecedence() > $elementToCompare->getPrecedence() ? 1 : -1;
            }
        );
        $aroundMethodInterceptors = [];
        foreach ($interceptorsReferences as $interceptorsReferenceName) {
            $aroundMethodInterceptors[] = $interceptorsReferenceName->buildAroundInterceptor($referenceSearchService, $endpointAnnotations, $interceptedInterface);
        }

        return $aroundMethodInterceptors;
    }

    public function getInterceptingInterface(): InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @return int
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public function buildAroundInterceptor(ReferenceSearchService $referenceSearchService, array $endpointAnnotations, InterfaceToCall $interceptedInterface): AroundMethodInterceptor
    {
        $referenceToCall = $this->directObject ?: $referenceSearchService->get($this->referenceName);

        $builtConverters = [];
        $hasMethodInvocation = false;
        $hasPayloadConverter = false;
        $interceptingInterface = $this->getInterceptingInterface();
        $interceptedInterfaceType = $interceptedInterface->getInterfaceType();
        foreach ($interceptingInterface->getInterfaceParameters() as $parameter) {
            foreach ($this->parameterConverters as $parameterConverter) {
                if ($parameterConverter->isHandling($parameter)) {
                    $builtConverters[] = $parameterConverter->build($referenceSearchService, $interceptingInterface, $parameter);
                    if ($parameterConverter instanceof PayloadConverter) {
                        $hasPayloadConverter = true;
                    }
                    continue 2;
                }
            }
            if ($parameter->canBePassedIn(TypeDescriptor::create(MethodInvocation::class))) {
                $builtConverters[] = new MethodInvocationConverter();
                $hasMethodInvocation = true;
                continue;
            }
            if ($interceptedInterfaceType && $parameter->canBePassedIn($interceptedInterfaceType)) {
                $builtConverters[] = new MethodInvocationObjectConverter();
                continue;
            }
            if ($converterBuilder = MethodArgumentsFactory::getAnnotationValueConverter($parameter, $interceptedInterface, $endpointAnnotations)) {
                $builtConverters[] = $converterBuilder->build($referenceSearchService, $interceptingInterface, $parameter);
                continue;
            }
            if ($parameter->canBePassedIn(TypeDescriptor::create(Message::class))) {
                $builtConverters[] = MessageConverter::create();
                continue;
            }

            if ($parameter->canBePassedIn(TypeDescriptor::create(ReferenceSearchService::class))) {
                $builtConverters[] = ValueConverter::createWith($referenceSearchService);
                continue;
            }

            if ($parameter->doesAllowNulls() && $parameter->isAnnotation()) {
                $builtConverters[] = ValueConverter::createWith(null);
                continue;
            }
            if (! $hasPayloadConverter) {
                $builtConverters[] = PayloadBuilder::create($parameter->getName())->build($referenceSearchService, $interceptingInterface, $parameter);
                $hasPayloadConverter = true;
                continue;
            } elseif ($parameter->getTypeDescriptor()->isNonCollectionArray()) {
                $builtConverters[] = AllHeadersBuilder::createWith($parameter->getName())->build($referenceSearchService, $interceptingInterface, $parameter);
                continue;
            }
            throw new InvalidArgumentException("Can't build around interceptor for {$this->interfaceToCall} because can't find converter for parameter {$parameter}");
        }

        return AroundMethodInterceptor::createWith(
            $referenceToCall,
            $this->interfaceToCall->getMethodName(),
            $referenceSearchService,
            $builtConverters,
            $hasMethodInvocation
        );
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
    public function doesItCutWith(InterfaceToCall $interfaceToCall, iterable $endpointAnnotations, InterfaceToCallRegistry $interfaceToCallRegistry): bool
    {
        return $this->pointcut->doesItCut($interfaceToCall, $endpointAnnotations, $interfaceToCallRegistry);
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
        return $this->interceptorName . $this->referenceName . $this->interfaceToCall;
    }
}

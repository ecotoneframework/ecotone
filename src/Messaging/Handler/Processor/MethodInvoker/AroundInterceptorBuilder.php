<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use function array_merge;

use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MethodInvocationConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MethodInvocationObjectConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PollingMetadataConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class AroundInterceptorBuilder implements InterceptorWithPointCut
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
    public static function create(string $referenceName, InterfaceToCall $interfaceToCall, int $precedence, string $pointcut = '', array $parameterConverters = []): self
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
     * @return self[]
     */
    public static function orderedInterceptors(array $interceptorsReferences): array
    {
        usort(
            $interceptorsReferences,
            function (AroundInterceptorBuilder $element, AroundInterceptorBuilder $elementToCompare) {
                if ($element->getPrecedence() == $elementToCompare->getPrecedence()) {
                    return 0;
                }

                return $element->getPrecedence() > $elementToCompare->getPrecedence() ? 1 : -1;
            }
        );

        return $interceptorsReferences;
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

    /**
     * @param AttributeDefinition[] $endpointAnnotations
     */
    public function compile(MessagingContainerBuilder $builder, array $endpointAnnotations, InterfaceToCall $interceptedInterface): Definition|null
    {
        $parameterAnnotationResolver = ParameterConverterAnnotationFactory::create();
        $parameterConvertersFromAttributes = $parameterAnnotationResolver->createParameterConverters($this->interfaceToCall);

        $alreadyResolvedParameterConverters = array_merge($parameterConvertersFromAttributes, $this->parameterConverters);

        /** @var array<Definition|Reference> $converterDefinitions */
        $converterDefinitions = [];
        $hasMethodInvocation = false;
        $hasPayloadConverter = false;
        $interceptingInterface = $this->getInterceptingInterface();
        $interceptedInterfaceType = $interceptedInterface->getInterfaceType();
        foreach ($interceptingInterface->getInterfaceParameters() as $parameter) {
            foreach ($alreadyResolvedParameterConverters as $parameterConverter) {
                if ($parameterConverter->isHandling($parameter)) {
                    $converterDefinitions[] = $parameterConverter->compile($builder, $interceptingInterface);
                    if ($parameterConverter instanceof PayloadConverter) {
                        $hasPayloadConverter = true;
                    }
                    continue 2;
                }
            }
            if ($parameter->canBePassedIn(TypeDescriptor::create(MethodInvocation::class))) {
                $converterDefinitions[] = new Definition(MethodInvocationConverter::class);
                $hasMethodInvocation = true;
                continue;
            }
            if ($interceptedInterfaceType && $parameter->canBePassedIn($interceptedInterfaceType)) {
                $converterDefinitions[] = new Definition(MethodInvocationObjectConverter::class);
                continue;
            }
            if ($attributeBuilder = MethodArgumentsFactory::getAnnotationValueConverter($parameter, $interceptedInterface, $endpointAnnotations)) {
                $converterDefinitions[] = $attributeBuilder->compile($builder, $interceptingInterface, $parameter);
                continue;
            }
            if ($parameter->canBePassedIn(TypeDescriptor::create(Message::class))) {
                $converterDefinitions[] = new Definition(MessageConverter::class);
                continue;
            }

            if ($parameter->canBePassedIn(TypeDescriptor::create(ReferenceSearchService::class))) {
                $converterDefinitions[] = new Definition(ValueConverter::class, [new Reference(ReferenceSearchService::class)]);
                continue;
            }

            if ($parameter->canBePassedIn(TypeDescriptor::create(PollingMetadata::class))) {
                $converterDefinitions[] = (new PollingMetadataConverterBuilder($parameter->getName()))->compile($builder, $interceptingInterface, $parameter);
                continue;
            }

            if ($parameter->doesAllowNulls() && $parameter->isAnnotation()) {
                $converterDefinitions[] = new Definition(ValueConverter::class, [null]);
                continue;
            }
            if (! $hasPayloadConverter) {
                $converterDefinitions[] = PayloadBuilder::create($parameter->getName())->compile($builder, $interceptingInterface);
                $hasPayloadConverter = true;
                continue;
            } elseif ($parameter->getTypeDescriptor()->isArrayButNotClassBasedCollection()) {
                $converterDefinitions[] = AllHeadersBuilder::createWith($parameter->getName())->compile($builder, $interceptingInterface);
                continue;
            } elseif ($parameter->getTypeDescriptor()->isClassOrInterface()) {
                $converterDefinitions[] = ReferenceBuilder::create($parameter->getName(), $parameter->getTypeHint())->compile($builder, $interceptingInterface);
                continue;
            }
            throw new InvalidArgumentException("Can't build around interceptor for {$this->interfaceToCall} because can't find converter for parameter {$parameter}");
        }

        return new Definition(AroundMethodInterceptor::class, [
            $this->directObject ?: new Reference($this->referenceName),
            InterfaceToCallReference::fromInstance($this->interfaceToCall),
            $converterDefinitions,
            $hasMethodInvocation,
        ]);
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

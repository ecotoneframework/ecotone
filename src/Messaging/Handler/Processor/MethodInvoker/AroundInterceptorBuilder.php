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
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut\IncorrectPointcutException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class AroundInterceptorBuilder implements InterceptorWithPointCut
{
    private int $precedence;
    private string $interceptorName;
    private Pointcut $pointcut;
    private ?object $directObject = null;
    private string $referenceName;
    /**
     * @var ParameterConverterBuilder[]
     */
    private array $parameterConverters;

    /**
     * @param ParameterConverterBuilder[] $parameterConverters
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
     * @param ParameterConverterBuilder[] $parameterConverters
     */
    private function initializePointcut(InterfaceToCall $interfaceToCall, Pointcut $pointcut, array $parameterConverters): Pointcut
    {
        if (! $pointcut->isEmpty()) {
            return $pointcut;
        }

        return Pointcut::initializeFrom($interfaceToCall, $parameterConverters);
    }

    /**
     * @param ParameterConverterBuilder[] $parameterConverters
     */
    public static function create(string $referenceName, InterfaceToCall $interfaceToCall, int $precedence, string $pointcut = '', array $parameterConverters = []): self
    {
        try {
            $pointcut = $pointcut ? Pointcut::createWith($pointcut) : Pointcut::createEmpty();
        } catch (IncorrectPointcutException $exception) {
            throw IncorrectPointcutException::create("Incorrect pointcut for {$interfaceToCall}. {$exception->getMessage()}");
        }

        return new self($precedence, $referenceName, $interfaceToCall, $pointcut, $parameterConverters);
    }

    public static function createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry $interfaceToCallRegistry, object $referenceObject, string $methodName, int $precedence, string $pointcut): self
    {
        $parameterAnnotationResolver = ParameterConverterAnnotationFactory::create();
        $interfaceToCall = $interfaceToCallRegistry->getFor($referenceObject, $methodName);
        $parameterConverters = $parameterAnnotationResolver->createParameterConverters($interfaceToCall);

        $aroundInterceptorReference               = new self($precedence, '', $interfaceToCall, Pointcut::createWith($pointcut), $parameterConverters);
        $aroundInterceptorReference->directObject = $referenceObject;

        return $aroundInterceptorReference;
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
    public function compileForInterceptedInterface(
        MessagingContainerBuilder $builder,
        InterfaceToCallReference  $interceptedInterfaceToCallReference,
        array                     $endpointAnnotations = []
    ): Definition {
        $interceptedInterface = $builder->getInterfaceToCall($interceptedInterfaceToCallReference);
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
                    $converterDefinitions[] = $parameterConverter->compile($interceptingInterface);
                    if ($parameterConverter instanceof PayloadConverter) {
                        $hasPayloadConverter = true;
                    }
                    continue 2;
                }
            }
            if ($parameter->canBePassedIn(Type::object(MethodInvocation::class))) {
                $converterDefinitions[] = new Definition(MethodInvocationConverter::class);
                $hasMethodInvocation = true;
                continue;
            }
            if ($interceptedInterfaceType && $parameter->canBePassedIn($interceptedInterfaceType)) {
                $converterDefinitions[] = new Definition(MethodInvocationObjectConverter::class);
                continue;
            }
            if ($attributeBuilder = MethodArgumentsFactory::getAnnotationValueConverter($parameter, $interceptedInterface, $endpointAnnotations)) {
                $converterDefinitions[] = $attributeBuilder->compile($interceptingInterface);
                continue;
            }
            if ($parameter->canBePassedIn(Type::object(Message::class))) {
                $converterDefinitions[] = new Definition(MessageConverter::class);
                continue;
            }

            if ($parameter->canBePassedIn(Type::object(ReferenceSearchService::class))) {
                $converterDefinitions[] = new Definition(ValueConverter::class, [new Reference(ReferenceSearchService::class)]);
                continue;
            }

            if ($parameter->canBePassedIn(Type::object(PollingMetadata::class))) {
                $converterDefinitions[] = (new PollingMetadataConverterBuilder($parameter->getName()))->compile($interceptingInterface);
                continue;
            }

            if ($parameter->doesAllowNulls() && $parameter->isAnnotation()) {
                $converterDefinitions[] = new Definition(ValueConverter::class, [null]);
                continue;
            }
            if (! $hasPayloadConverter) {
                $converterDefinitions[] = PayloadBuilder::create($parameter->getName())->compile($interceptingInterface);
                $hasPayloadConverter = true;
                continue;
            } elseif ($parameter->getTypeDescriptor()->isArrayButNotClassBasedCollection()) {
                $converterDefinitions[] = AllHeadersBuilder::createWith($parameter->getName())->compile($interceptingInterface);
                continue;
            } elseif ($parameter->getTypeDescriptor()->isClassOrInterface()) {
                $converterDefinitions[] = ReferenceBuilder::create($parameter->getName(), $parameter->getTypeHint())->compile($interceptingInterface);
                continue;
            }
            throw InvalidArgumentException::create("Can't build around interceptor for {$this->interfaceToCall} because can't find converter for parameter {$parameter}");
        }

        if ($this->interfaceToCall->canReturnValue() && ! $hasMethodInvocation) {
            throw InvalidArgumentException::create("Trying to register {$this->interfaceToCall} as Around Advice which can return value, but doesn't control invocation using " . MethodInvocation::class . '. Have you wanted to register Before/After Advice or forgot to type hint MethodInvocation?');
        }

        return new Definition(AroundMethodInterceptor::class, [
            $this->directObject ?: new Reference($this->referenceName),
            $this->interfaceToCall->getMethodName(),
            $converterDefinitions,
            $hasMethodInvocation,
        ]);
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

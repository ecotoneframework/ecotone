<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvokerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class TransformerBuilder
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class TransformerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters
{
    private string $objectToInvokeReferenceName;
    private ?DefinedObject $directObject = null;
    private array $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private array $requiredReferenceNames = [];
    private ?string $expression = null;

    private function __construct(string $objectToInvokeReference, private string|InterfaceToCall $methodNameOrInterface)
    {
        $this->objectToInvokeReferenceName = $objectToInvokeReference;

        if ($objectToInvokeReference) {
            $this->requiredReferenceNames[] = $objectToInvokeReference;
        }
    }

    public static function create(string $objectToInvokeReference, InterfaceToCall $interfaceToCall): self
    {
        return new self($objectToInvokeReference, $interfaceToCall);
    }

    /**
     * @param array|string[] $messageHeaders
     * @return TransformerBuilder
     */
    public static function createHeaderEnricher(array $messageHeaders): self
    {
        $transformerBuilder = new self('', 'transform');
        $transformerBuilder->setDirectObjectToInvoke(HeaderEnricher::create($messageHeaders));

        return $transformerBuilder;
    }

    /**
     * @param array|string[] $mappedHeaders ["secret" => "token"]
     * @return TransformerBuilder
     */
    public static function createHeaderMapper(array $mappedHeaders): self
    {
        $transformerBuilder = new self('', 'transform');
        $transformerBuilder->setDirectObjectToInvoke(HeaderMapperTransformer::create($mappedHeaders));

        return $transformerBuilder;
    }

    public static function createWithDirectObject(DefinedObject $referenceObject, string $methodName): self
    {
        $transformerBuilder = new self('', $methodName);
        $transformerBuilder->setDirectObjectToInvoke($referenceObject);

        return $transformerBuilder;
    }

    public static function createWithExpression(string $expression): self
    {
        $transformerBuilder = new self('', 'transform');

        return $transformerBuilder->setExpression($expression);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        if ($this->expression) {
            return $interfaceToCallRegistry->getFor(ExpressionTransformer::class, 'transform');
        }

        return $this->methodNameOrInterface instanceof InterfaceToCall
            ? $this->methodNameOrInterface
            : $interfaceToCallRegistry->getFor($this->directObject, $this->getMethodName());
    }

    /**
     * @param array|ParameterConverter[] $methodParameterConverterBuilders
     *
     * @return TransformerBuilder
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        [$objectToInvokeOn, $interfaceToCallReference, $interfaceToCall] = $this->prepare($builder);

        $newImplementation = MessageProcessorActivatorBuilder::create()
            ->withEndpointId($this->getEndpointId())
            ->withRequiredInterceptorNames($this->requiredReferenceNames)
            ->withInputChannelName($this->getInputMessageChannelName())
            ->withOutputMessageChannel($this->getOutputMessageChannelName())
            ->withEndpointAnnotations($this->getEndpointAnnotations())
            ->chainInterceptedProcessor(
                MethodInvokerBuilder::create(
                    $objectToInvokeOn,
                    $interfaceToCallReference,
                    $this->methodParameterConverterBuilders
                )
                ->withResultToMessageConverter(
                    new Definition(TransformerResultToMessageConverter::class, [$interfaceToCall->getReturnType()]),
                )
            );

        return $newImplementation->compile($builder);
    }

    public function compileProcessor(MessagingContainerBuilder $builder): Definition
    {
        [$objectToInvokeOn, $interfaceToCallReference, $interfaceToCall] = $this->prepare($builder);

        return MethodInvokerBuilder::create(
            $objectToInvokeOn,
            $interfaceToCallReference,
            $this->methodParameterConverterBuilders
        )->compile($builder);
    }

    private function setDirectObjectToInvoke(DefinedObject $objectToInvoke): void
    {
        $this->directObject = $objectToInvoke;
    }

    /**
     * @param string $expression
     *
     * @return TransformerBuilder
     */
    private function setExpression(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    public function __toString()
    {
        $reference = $this->directObject ? get_class($this->directObject) : $this->objectToInvokeReferenceName;

        return sprintf('Transformer - %s:%s with name `%s` for input channel `%s`', $reference, $this->getMethodName(), $this->getEndpointId(), $this->getInputMessageChannelName());
    }

    private function getMethodName(): string|InterfaceToCall
    {
        return $this->methodNameOrInterface instanceof InterfaceToCall
            ? $this->methodNameOrInterface->getMethodName()
            : $this->methodNameOrInterface;
    }

    public function prepare(MessagingContainerBuilder $builder): array
    {
        if ($this->expression) {
            $objectToInvokeOn = new Definition(ExpressionTransformer::class, [$this->expression, new Reference(ExpressionEvaluationService::REFERENCE), new Reference(ReferenceSearchService::class)]);
            $interfaceToCallReference = new InterfaceToCallReference(ExpressionTransformer::class, 'transform');
        } else {
            $objectToInvokeOn = $this->directObject ?: new Reference($this->objectToInvokeReferenceName);
            if ($this->methodNameOrInterface instanceof InterfaceToCall) {
                $interfaceToCallReference = InterfaceToCallReference::fromInstance($this->methodNameOrInterface);
            } else {
                $className = $this->directObject ? \get_class($objectToInvokeOn) : $this->objectToInvokeReferenceName;
                $interfaceToCallReference = new InterfaceToCallReference($className, $this->getMethodName());
            }
        }

        $interfaceToCall = $builder->getInterfaceToCall($interfaceToCallReference);

        if (! $interfaceToCall->canReturnValue()) {
            throw InvalidArgumentException::create("Can't create transformer for {$interfaceToCall}, because method has no return value");
        }

        return [$objectToInvokeOn, $interfaceToCallReference, $interfaceToCall];
    }
}

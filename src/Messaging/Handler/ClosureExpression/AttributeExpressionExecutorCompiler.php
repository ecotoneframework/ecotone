<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AttributeBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\Assert;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

/**
 * licence Enterprise
 */
final class AttributeExpressionExecutorCompiler
{
    /**
     * Compiles attribute expression into container definition with all closure parameter converters resolved at build time.
     * Declaration may be omitted only for string expressions, where attribute is serializable as it is.
     */
    public static function compile(WithExpression $attributeWithExpression, ?AttributeDeclaration $attributeDeclaration): Definition
    {
        if ($attributeDeclaration === null) {
            Assert::isFalse($attributeWithExpression->getExpression() instanceof Closure, sprintf('Closure expression inside %s attribute requires attribute declaration to compile.', get_class($attributeWithExpression)));

            return self::executorDefinition(
                AttributeDefinition::fromObject($attributeWithExpression),
                $attributeWithExpression->getExpression(),
                get_class($attributeWithExpression),
                null,
            );
        }

        return self::executorDefinition(
            $attributeDeclaration->toAttributeDefinition(),
            $attributeWithExpression->getExpression(),
            $attributeDeclaration->getClassName(),
            $attributeDeclaration->getMethodName(),
        );
    }

    /**
     * Compiles attribute expression bound to plain context variables, for evaluation without Message.
     */
    public static function compileForContext(WithExpression $attributeWithExpression, AttributeDeclaration $attributeDeclaration): Definition
    {
        $parameterSpecifications = [];
        $expression = $attributeWithExpression->getExpression();
        if ($expression instanceof Closure) {
            foreach ((new ReflectionFunction($expression))->getParameters() as $reflectionParameter) {
                $parameterSpecifications[] = [
                    'name' => $reflectionParameter->getName(),
                    'hasDefaultValue' => $reflectionParameter->isDefaultValueAvailable(),
                    'defaultValue' => $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null,
                ];
            }
        }

        return new Definition(AttributeExpressionContextExecutor::class, [
            $attributeDeclaration->toAttributeDefinition(),
            Reference::to(ExpressionEvaluationService::REFERENCE),
            $parameterSpecifications,
        ]);
    }

    /**
     * Provides converter for interceptor parameter marked with ExecutorFor attribute.
     * Injects executor carrying related intercepted endpoint attribute together with its compiled expression program, null when attribute is not present.
     *
     * @param AttributeDefinition[] $endpointAnnotations
     */
    public static function interceptorParameterConverterFor(InterfaceParameter $interfaceParameter, InterfaceToCall $interceptedInterface, array $endpointAnnotations): ?ValueBuilder
    {
        $executorForAttributes = $interfaceParameter->getAnnotationsOfType(ExecutorFor::class);
        if ($executorForAttributes === []) {
            return null;
        }

        /** @var ExecutorFor $executorFor */
        $executorFor = $executorForAttributes[0];

        return new ValueBuilder(
            $interfaceParameter->getName(),
            self::executorDefinitionForInterceptedAttribute($executorFor->attributeClassName, $interceptedInterface, $endpointAnnotations),
        );
    }

    /**
     * @param AttributeDefinition[] $endpointAnnotations
     */
    private static function executorDefinitionForInterceptedAttribute(string $attributeClassName, InterfaceToCall $interceptedInterface, array $endpointAnnotations): ?Definition
    {
        foreach ([true, false] as $exactMatch) {
            foreach ($endpointAnnotations as $endpointAnnotation) {
                if (self::matchesAttributeClass($endpointAnnotation->getClassName(), $attributeClassName, $exactMatch)) {
                    return self::executorDefinition(
                        $endpointAnnotation,
                        self::expressionOf($endpointAnnotation->instance()),
                        $interceptedInterface->getInterfaceName(),
                        $interceptedInterface->getMethodName(),
                    );
                }
            }

            foreach ($interceptedInterface->getMethodAnnotations() as $annotation) {
                if (self::matchesAttributeClass(get_class($annotation), $attributeClassName, $exactMatch)) {
                    return self::executorDefinition(
                        AttributeDefinition::fromObject($annotation, new AttributeDeclaration(get_class($annotation), $interceptedInterface->getInterfaceName(), $interceptedInterface->getMethodName())),
                        self::expressionOf($annotation),
                        $interceptedInterface->getInterfaceName(),
                        $interceptedInterface->getMethodName(),
                    );
                }
            }
            foreach ($interceptedInterface->getClassAnnotations() as $annotation) {
                if (self::matchesAttributeClass(get_class($annotation), $attributeClassName, $exactMatch)) {
                    return self::executorDefinition(
                        AttributeDefinition::fromObject($annotation, new AttributeDeclaration(get_class($annotation), $interceptedInterface->getInterfaceName())),
                        self::expressionOf($annotation),
                        $interceptedInterface->getInterfaceName(),
                        null,
                    );
                }
            }
        }

        return null;
    }

    private static function expressionOf(object $attribute): Closure|string|null
    {
        return $attribute instanceof WithExpression ? $attribute->getExpression() : null;
    }

    private static function executorDefinition(AttributeDefinition $attributeArgument, Closure|string|null $expression, string $ownerClassName, ?string $ownerMethodName): Definition
    {
        $closureParameterResolvers = [];
        if ($expression instanceof Closure) {
            $reflectionParameters = (new ReflectionFunction($expression))->getParameters();
            $closureParameterResolvers = self::parameterResolverDefinitions(
                $reflectionParameters,
                self::closureInterfaceToCall($ownerClassName, $ownerMethodName, $reflectionParameters),
                $ownerClassName,
                $ownerMethodName,
            );
        }

        return new Definition(AttributeExpressionExecutor::class, [
            $attributeArgument,
            Reference::to(ExpressionEvaluationService::REFERENCE),
            $closureParameterResolvers,
        ]);
    }

    private static function matchesAttributeClass(string $annotationClassName, string $attributeClassName, bool $exactMatch): bool
    {
        if ($exactMatch) {
            return $annotationClassName === $attributeClassName;
        }

        return is_a($annotationClassName, $attributeClassName, true);
    }

    /**
     * @param ReflectionParameter[] $reflectionParameters
     * @return Definition[]
     */
    private static function parameterResolverDefinitions(array $reflectionParameters, InterfaceToCall $interfaceToCall, string $ownerClassName, ?string $ownerMethodName): array
    {
        $parameterResolvers = [];
        foreach ($reflectionParameters as $index => $reflectionParameter) {
            $interfaceParameter = $interfaceToCall->getParameterWithName($reflectionParameter->getName());
            self::ensureNoNestedClosureExpression($interfaceParameter, $interfaceToCall);

            $converterBuilder = ParameterConverterAnnotationFactory::getConverterFor($interfaceParameter, $interfaceToCall);
            $resolvesFromAdditionalContext = $converterBuilder === null || $converterBuilder instanceof MessageConverterBuilder;
            if ($converterBuilder === null) {
                $converterBuilder = self::declaredAttributeConverterBuilderFor($interfaceParameter, $ownerClassName, $ownerMethodName);
                if ($converterBuilder !== null) {
                    $resolvesFromAdditionalContext = false;
                }
            }
            if ($converterBuilder === null) {
                $converterBuilder = self::defaultConverterBuilderFor($interfaceParameter, $index === 0);
            }

            $parameterResolvers[] = new Definition(ClosureParameterResolver::class, [
                $reflectionParameter->getName(),
                $converterBuilder?->compile($interfaceToCall),
                $resolvesFromAdditionalContext,
                $reflectionParameter->isDefaultValueAvailable(),
                $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null,
            ]);
        }

        return $parameterResolvers;
    }

    /**
     * @param ReflectionParameter[] $reflectionParameters
     */
    private static function closureInterfaceToCall(string $className, ?string $methodName, array $reflectionParameters): InterfaceToCall
    {
        $interfaceParameters = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $parameterType = Type::createWithDocBlock($reflectionParameter->getType() ? (string) $reflectionParameter->getType() : null, null);
            $annotations = [];
            foreach ($reflectionParameter->getAttributes() as $attribute) {
                $annotations[] = $attribute->newInstance();
            }

            $interfaceParameters[] = InterfaceParameter::create(
                $reflectionParameter->getName(),
                $parameterType,
                $reflectionParameter->getType() ? $reflectionParameter->getType()->allowsNull() : true,
                $reflectionParameter->isDefaultValueAvailable(),
                $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null,
                $parameterType->isAttribute(),
                $annotations,
            );
        }

        return new InterfaceToCall(
            $className,
            ($methodName ?? 'closure') . ' expression',
            [],
            [],
            $interfaceParameters,
            null,
            true,
            false,
        );
    }

    private static function ensureNoNestedClosureExpression(InterfaceParameter $interfaceParameter, InterfaceToCall $interfaceToCall): void
    {
        foreach ($interfaceParameter->getAnnotations() as $annotation) {
            if ($annotation instanceof WithExpression && $annotation->getExpression() instanceof Closure) {
                throw ConfigurationException::create(sprintf('Closure expression inside %s attribute cannot be used on closure expression parameter `%s` in %s. Nested closure expressions are not supported.', get_class($annotation), $interfaceParameter->getName(), $interfaceToCall));
            }
        }
    }

    /**
     * Resolves closure parameter type hinted with an Attribute declared on the owning method or class,
     * so expression may adapt its behaviour to configuration declared next to the endpoint.
     */
    private static function declaredAttributeConverterBuilderFor(InterfaceParameter $interfaceParameter, string $ownerClassName, ?string $ownerMethodName): ?ParameterConverterBuilder
    {
        if (! $interfaceParameter->isAnnotation()) {
            return null;
        }

        $parameterType = $interfaceParameter->getTypeDescriptor()->withoutNull();

        if ($ownerMethodName !== null) {
            foreach ((new ReflectionMethod($ownerClassName, $ownerMethodName))->getAttributes() as $attribute) {
                if (Type::object($attribute->getName())->equals($parameterType)) {
                    return new AttributeBuilder($interfaceParameter->getName(), $attribute->newInstance(), $ownerClassName, $ownerMethodName);
                }
            }
        }

        foreach ((new ReflectionClass($ownerClassName))->getAttributes() as $attribute) {
            if (Type::object($attribute->getName())->equals($parameterType)) {
                return new AttributeBuilder($interfaceParameter->getName(), $attribute->newInstance(), $ownerClassName, null);
            }
        }

        return null;
    }

    private static function defaultConverterBuilderFor(InterfaceParameter $interfaceParameter, bool $isFirstParameter): ?ParameterConverterBuilder
    {
        if ($isFirstParameter) {
            return PayloadBuilder::create($interfaceParameter->getName());
        }
        if ($interfaceParameter->getTypeDescriptor()->isClassOrInterface()) {
            return ReferenceBuilder::create($interfaceParameter->getName(), $interfaceParameter->getTypeHint());
        }

        return null;
    }
}

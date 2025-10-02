<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\IgnoreDocblockTypeHint;
use Ecotone\Messaging\Attribute\IsAbstract;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\AttributeReference;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceParameterReference;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Type\TypeContext;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Error;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Class TypeResolver
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class TypeResolver
{
    private const SINGLE_USE_STATEMENTS_REGEX = '/^[^\S\r\na-zA-Z0-9]*use[\s]*([^;\n\}]*)[\s]*;$/m';
    private const GROUP_USE_STATEMENTS_REGEX = '/^[^\S\r\n]*use[[\s]*([^;\n]*)[\s]*{([a-zA-Z0-9\s\n\r,]*)};$/m';

    private const METHOD_DOC_BLOCK_TYPE_HINT_REGEX = '~@param[\s]*([^\n\$]*?)[\s]*\$([a-zA-Z0-9]*)~';
    private const METHOD_RETURN_TYPE_HINT_REGEX = '~@return[\s]*([^\n]*)~';
    private const CLASS_PROPERTY_TYPE_HINT_REGEX = "#@var[\s]*([^\n\$\s]*)#";

    private AnnotationResolver $annotationParser;

    private function __construct(AnnotationResolver $annotationParser)
    {
        $this->annotationParser = $annotationParser;
    }

    public static function create(): self
    {
        return new self(new AnnotationResolver\AttributeResolver());
    }

    public static function createWithAnnotationParser(AnnotationResolver $annotationParser): self
    {
        return new self($annotationParser);
    }

    public function getMethodParameters(ReflectionClass $analyzedClass, string $methodName): iterable
    {
        $parameters = [];
        $reflectionMethod = $analyzedClass->getMethod($methodName);
        $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($analyzedClass, $analyzedClass, $methodName);
        $context = self::getTypeContext($analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName));
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = Type::createWithDocBlock(
                $parameter->getType(),
                array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : '',
                $context,
            );

            $parameterAttributes = [];
            foreach ($parameter->getAttributes() as $attribute) {
                try {
                    $parameterAttributes[] = $attribute->newInstance();
                } catch (Error $e) {
                    if (\preg_match('/Attribute "(.*)" cannot target parameter/', $e->getMessage())) {
                        // Do nothing: it is an attribute targeting a property promoted from a parameter
                    } else {
                        throw $e;
                    }
                }
            }

            $parameters[] = InterfaceParameter::create(
                $parameter->getName(),
                $parameterType,
                $parameter->getType() ? $parameter->getType()->allowsNull() : true,
                $parameter->isDefaultValueAvailable(),
                $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                $parameterType->isAttribute(),
                $parameterAttributes
            );
        }

        return $parameters;
    }

    public function registerInterfaceToCallDefinition(ContainerBuilder $builder, InterfaceToCallReference $reference): InterfaceToCallReference
    {
        if ($builder->has($reference)) {
            return $reference;
        }
        $interfaceName = $reference->getClassName();
        $methodName = $reference->getMethodName();

        try {
            $reflectionClass = new ReflectionClass($interfaceName);
            $reflectionMethod = self::getMethodOwnerClass($reflectionClass, $methodName)->getMethod($methodName);
            $parametersReferences = $this->registerMethodParametersDefinitions($builder, $reflectionClass, $methodName);
            $returnType = $this->getReturnType($reflectionClass, $methodName);

            $classAnnotations = [];
            foreach ($reflectionClass->getAttributes() as $attribute) {
                if (class_exists($attribute->getName())) {
                    $classAnnotations[] = self::registerAttributeDefinition($builder, AttributeDefinition::fromReflection($attribute), $interfaceName, null);
                }
            }
            if ($reflectionClass->isAbstract() && ! $reflectionClass->isInterface()) {
                $classAnnotations[] = self::registerAttributeDefinition($builder, new AttributeDefinition(IsAbstract::class), $interfaceName, null);
            }
            $methodAnnotations = [];
            foreach ($reflectionMethod->getAttributes() as $attribute) {
                if (class_exists($attribute->getName())) {
                    $methodAnnotations[] = self::registerAttributeDefinition($builder, AttributeDefinition::fromReflection($attribute), $interfaceName, $methodName);
                }
            }

            $doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
            $isStaticallyCalled       = $reflectionMethod->isStatic();
        } catch (TypeDefinitionException $definitionException) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has problem with type declaration. {$definitionException->getMessage()}");
        }

        $builder->register(
            $reference,
            new Definition(InterfaceToCall::class, [
                $reflectionClass->getName(),
                $reflectionMethod->getName(),
                $classAnnotations,
                $methodAnnotations,
                $parametersReferences,
                $returnType,
                $doesReturnTypeAllowNulls,
                $isStaticallyCalled,
            ])
        );

        return $reference;
    }

    /**
     * @return InterfaceParameterReference[]
     */
    public function registerMethodParametersDefinitions(ContainerBuilder $builder, ReflectionClass $reflectionClass, string $methodName): array
    {
        $parameters = [];
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($reflectionClass, $reflectionClass, $methodName);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = Type::createWithDocBlock(
                $parameter->getType(),
                array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : '',
                self::getTypeContext($reflectionClass, $reflectionClass, self::getMethodDeclaringClass($reflectionClass, $methodName)),
            );
            $isAnnotation = false;
            if ($parameterType->isClassOrInterface() && ! $parameterType->isCompoundObjectType() && ! $parameterType->isUnionType()) {
                $classDefinition = ClassDefinition::createUsingAnnotationParser($parameterType, $this->annotationParser);
                $isAnnotation = $classDefinition->isAnnotation();
            }

            $parameterAttributes = [];
            foreach ($parameter->getAttributes() as $attribute) {
                $parameterAttributes[] = AttributeDefinition::fromReflection($attribute);
            }

            $parameters[] = $reference = new InterfaceParameterReference($reflectionClass->getName(), $reflectionMethod->getName(), $parameter->getName());

            $builder->register(
                $reference,
                new Definition(InterfaceParameter::class, [
                    $parameter->getName(),
                    $parameterType,
                    $parameter->getType() ? $parameter->getType()->allowsNull() : true,
                    $parameter->isDefaultValueAvailable(),
                    $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    $isAnnotation,
                    $parameterAttributes,
                ])
            );
        }

        return $parameters;
    }

    public function registerAttribute(ContainerBuilder $builder, AttributeReference $attributeReference): void
    {
        $className = $attributeReference->getClassName();
        if ($methodName = $attributeReference->getMethodName()) {
            $reflection = new ReflectionMethod($className, $methodName);
        } else {
            $reflection = new ReflectionClass($className);
        }
        $attributes = $reflection->getAttributes($attributeReference->getAttributeClass());
        if ($attributes > 1) {
            // Warning ?
        } elseif ($attributes === 0) {
            throw new InvalidArgumentException("Invalid attribute reference {$attributeReference}");
        }
        self::registerAttributeDefinition($builder, AttributeDefinition::fromReflection($attributes[0]), $className, $methodName);
    }

    private function registerAttributeDefinition(ContainerBuilder $builder, AttributeDefinition $attributeDefinition, string $className, ?string $methodName = null): Definition|Reference
    {
        $reference = new AttributeReference($attributeDefinition->getClassName(), $className, $methodName);
        if (! $builder->has($reference)) {
            $builder->register($reference, $attributeDefinition);
        }
        return $attributeDefinition;
    }

    /**
     * @param ReflectionClass $thisClass
     * @param ReflectionClass $analyzedClass
     * @param string $methodName
     * @return array
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getMethodDocBlockParameterTypeHints(ReflectionClass $thisClass, ReflectionClass $analyzedClass, string $methodName): array
    {
        $analyzedClass = $this->getMethodOwnerClass($analyzedClass, $methodName);
        $methodReflection = $analyzedClass->getMethod($methodName);

        $docComment = $this->getDocComment($analyzedClass, $methodReflection);
        preg_match_all(self::METHOD_DOC_BLOCK_TYPE_HINT_REGEX, $docComment, $matchedDocBlockParameterTypes);

        $docBlockParameterTypeHints = [];
        $matchAmount = count($matchedDocBlockParameterTypes[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $docBlockParameterTypeHints[$matchedDocBlockParameterTypes[2][$matchIndex]] = $matchedDocBlockParameterTypes[1][$matchIndex];
        }

        if ($thisClass !== $analyzedClass) {
            $mappedParameterNames = [];
            $thisReflectionMethodParameters = $thisClass->getMethod($methodReflection->getName())->getParameters();
            $reflectionParameters = $methodReflection->getParameters();
            for ($parameterIndex = 0; $parameterIndex < count($reflectionParameters); $parameterIndex++) {
                if (array_key_exists($reflectionParameters[$parameterIndex]->getName(), $docBlockParameterTypeHints)) {
                    $mappedParameterNames[$thisReflectionMethodParameters[$parameterIndex]->getName()] = $docBlockParameterTypeHints[$reflectionParameters[$parameterIndex]->getName()];
                }
            }
            $docBlockParameterTypeHints = $mappedParameterNames;
        }

        return $docBlockParameterTypeHints;
    }

    /**
     * @param ReflectionClass $thisClass
     * @param ReflectionClass $analyzedClass
     * @param string $methodName
     * @return array|ReflectionClass
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function getMethodOwnerClass(ReflectionClass $analyzedClass, string $methodName)
    {
        $methodReflection = $analyzedClass->getMethod($methodName);
        $declaringClass = self::getMethodDeclaringClass($analyzedClass, $methodReflection->getName());
        if ($analyzedClass->getName() !== $declaringClass->getName()) {
            return self::getMethodOwnerClass($declaringClass, $methodName);
        }
        foreach ($analyzedClass->getTraits() as $trait) {
            if ($trait->hasMethod($methodReflection->getName()) && ! self::wasTraitOverwritten($methodReflection, $trait)) {
                return self::getMethodOwnerClass($trait, $methodName);
            }
        }

        return $analyzedClass;
    }

    private function getDocComment(ReflectionClass $analyzedClass, ReflectionMethod $methodReflection): string
    {
        $docComment = $methodReflection->getDocComment();
        if (! $docComment || $this->isIgnoringDocblockTypeHints($analyzedClass, $methodReflection)) {
            return '';
        }

        return $docComment;
    }

    private function isIgnoringDocblockTypeHints(ReflectionClass $analyzedClass, ReflectionMethod $methodReflection): bool
    {
        return (bool)$methodReflection->getAttributes(IgnoreDocblockTypeHint::class) || (bool)$analyzedClass->getAttributes(IgnoreDocblockTypeHint::class);
    }

    private function getTypeContext(ReflectionClass $thisClass, ReflectionClass $analyzedClass, ReflectionClass $declaringClass): TypeContext
    {
        $classContents = file_get_contents($analyzedClass->getFileName());
        $statements = array_merge($this->getSingleUseStatements($classContents), $this->getGroupUseStatements($classContents));
        $parentClass = $thisClass->getParentClass() ?: null;
        return new TypeContext(
            $thisClass->getName(),
            $declaringClass->getName(),
            $parentClass?->getName(),
            $thisClass->getNamespaceName(),
            aliases: $statements
        );
    }

    private function getGroupUseStatements(string $classContents): array
    {
        $foundClasses = [];
        preg_match_all(self::GROUP_USE_STATEMENTS_REGEX, $classContents, $foundGroupUseStatements);
        for ($useStatementIndex = 0; $useStatementIndex < count($foundGroupUseStatements[0]); $useStatementIndex++) {
            foreach (explode(',', $foundGroupUseStatements[2][$useStatementIndex]) as $singleUseStatement) {
                $foundClasses[] = trim($foundGroupUseStatements[1][$useStatementIndex]) . trim($singleUseStatement);
            }
        }

        $useStatementAssosciative = [];
        foreach ($foundClasses as $foundClass) {
            [$classNameAlias, $className] = $this->getClassNameAndAlias($foundClass);
            $useStatementAssosciative[$classNameAlias] = $className;
        }

        return $useStatementAssosciative;
    }

    private function getSingleUseStatements(string $classContents): array
    {
        preg_match_all(self::SINGLE_USE_STATEMENTS_REGEX, $classContents, $foundUseStatements);

        $useStatements = [];
        $matchAmount = count($foundUseStatements[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $className = $foundUseStatements[1][$matchIndex];
            [$classNameAlias, $className] = $this->getClassNameAndAlias($className);

            $useStatements[$classNameAlias] = $className;
        }

        return $useStatements;
    }

    /**
     * @param $alias
     * @return bool
     */
    private function hasUseStatementAlias($alias): bool
    {
        return count($alias) === 2;
    }

    /**
     * @param ReflectionClass $analyzedClass
     * @param string $methodName
     * @return ReflectionClass
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private static function getMethodDeclaringClass(ReflectionClass $analyzedClass, string $methodName): ReflectionClass
    {
        return $analyzedClass->getMethod($methodName)->getDeclaringClass();
    }

    /**
     * @param string $className
     * @return ClassPropertyDefinition[]
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getClassProperties(string $className): iterable
    {
        $reflectionClass = new ReflectionClass($className);
        $annotationParser = $this->getAnnotationParser($className);

        $classProperties = [];
        $parent = $reflectionClass;
        foreach ($reflectionClass->getProperties() as $property) {
            $classProperties[] = $this->createClassPropertyUsingTraitsIfExists($reflectionClass, $property, $annotationParser, $reflectionClass);
        }
        while ($parent = $parent->getParentClass()) {
            foreach ($parent->getProperties() as $property) {
                $classProperties[] = $this->createClassPropertyUsingTraitsIfExists($reflectionClass, $property, $annotationParser, $parent);
            }
        }

        return array_unique($classProperties);
    }

    private function getAnnotationParser(string $className): AnnotationResolver
    {
        return $this->annotationParser ? $this->annotationParser : InMemoryAnnotationFinder::createFrom([$className]);
    }

    private function getPropertyDocblockTypeHint(ReflectionProperty $reflectionProperty): ?string
    {
        $docComment = $reflectionProperty->getDocComment();
        preg_match_all(self::CLASS_PROPERTY_TYPE_HINT_REGEX, $docComment, $matchedDocBlockParameterTypes);

        return $matchedDocBlockParameterTypes[1][0] ?? null;
    }

    public function getReturnType(ReflectionClass $analyzedClass, string $methodName): Type
    {
        $reflectionMethod = $analyzedClass->getMethod($methodName);

        return Type::createWithDocBlock(
            $this->getTypeFromReflection($reflectionMethod->getReturnType()),
            $this->getReturnTypeDocBlockParameterTypeHint($analyzedClass, $methodName),
            self::getTypeContext($analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName)),
        );
    }

    /**
     * @throws ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getReturnTypeDocBlockParameterTypeHint(ReflectionClass $analyzedClass, string $methodName): ?string
    {
        $analyzedClass = $this->getMethodOwnerClass($analyzedClass, $methodName);
        $methodReflection = $analyzedClass->getMethod($methodName);

        $docComment = $this->getDocComment($analyzedClass, $methodReflection);
        preg_match(self::METHOD_RETURN_TYPE_HINT_REGEX, $docComment, $matchedDocBlockReturnType);

        if (isset($matchedDocBlockReturnType[1])) {
            return $matchedDocBlockReturnType[1];
        }

        return null;
    }

    /**
     * @param ReflectionMethod $methodReflection
     * @param ReflectionClass $trait
     * @return bool
     * @throws ReflectionException
     */
    private static function wasTraitOverwritten(ReflectionMethod $methodReflection, ReflectionClass $trait): bool
    {
        return $methodReflection->getFileName() !== $trait->getMethod($methodReflection->getName())->getFileName();
    }

    private function createClassProperty(ReflectionClass $declaringClass, AnnotationResolver $annotationParser, ReflectionProperty $property, ReflectionClass $thisClass): ClassPropertyDefinition
    {
        $type = Type::createWithDocBlock(
            $this->getTypeFromReflection($property->getType()),
            $this->getPropertyDocblockTypeHint($property),
            self::getTypeContext($thisClass, $thisClass, $declaringClass)
        );
        $isNullable = $property->hasType() ? $property->getType()->allowsNull() : true;


        $annotations = $annotationParser->getAnnotationsForProperty($declaringClass->getName(), $property->getName());
        if ($property->isPrivate()) {
            $classProperty = ClassPropertyDefinition::createPrivate(
                $property->getName(),
                $type,
                $isNullable,
                $property->isStatic(),
                $annotations
            );
        } elseif ($property->isProtected()) {
            $classProperty = ClassPropertyDefinition::createProtected(
                $property->getName(),
                $type,
                $isNullable,
                $property->isStatic(),
                $annotations
            );
        } else {
            $classProperty = ClassPropertyDefinition::createPublic(
                $property->getName(),
                $type,
                $isNullable,
                $property->isStatic(),
                $annotations
            );
        }
        return $classProperty;
    }

    private function createClassPropertyUsingTraitsIfExists(ReflectionClass $reflectionClassOrTrait, ReflectionProperty $property, AnnotationResolver $annotationParser, ReflectionClass $declaringClass)
    {
        foreach ($reflectionClassOrTrait->getTraits() as $trait) {
            foreach ($trait->getProperties() as $traitProperty) {
                if ($traitProperty->getName() === $property->getName()) {
                    return $this->createClassPropertyUsingTraitsIfExists(
                        $trait,
                        $traitProperty,
                        $annotationParser,
                        $declaringClass
                    );
                }
            }
        }

        return $this->createClassProperty(
            $declaringClass,
            $annotationParser,
            $property,
            $reflectionClassOrTrait,
        );
    }

    private function getTypeFromReflection(?ReflectionType $returnType): string
    {
        if ($returnType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($returnType->getTypes() as $type) {
                $types[] = $type;
            }

            $returnTypeName = implode('|', $types);
        } else {
            $returnTypeName = $returnType ? $returnType->getName() : '';
        }

        return $returnTypeName;
    }

    private function getClassNameAndAlias(mixed $className): array
    {
        $classNameAlias = null;
        if (($alias = explode(' as ', $className)) && $this->hasUseStatementAlias($alias)) {
            $className      = $alias[0];
            $classNameAlias = $alias[1];
        }

        $splittedClassName = explode('\\', $className);
        if ($className[0] !== '\\') {
            $className = '\\' . $className;
        }
        if (! $classNameAlias) {
            $classNameAlias = end($splittedClassName);
        }

        return [$classNameAlias, $className];
    }
}

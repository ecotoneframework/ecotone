<?php

namespace Ecotone\Messaging\Handler;

use Doctrine\Common\Annotations\Annotation;
use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\IgnoreDocblockTypeHint;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class TypeResolver
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class TypeResolver
{
    private const COLLECTION_TYPE_REGEX       = "/[a-zA-Z0-9]*<([^<]*)>/";
    private const SINGLE_USE_STATEMENTS_REGEX = '/^[^\S\r\na-zA-Z0-9]*use[\s]*([^;\n\}]*)[\s]*;$/m';
    private const GROUP_USE_STATEMENTS_REGEX = '/^[^\S\r\n]*use[[\s]*([^;\n]*)[\s]*{([a-zA-Z0-9\s\n\r,]*)};$/m';

    private const METHOD_DOC_BLOCK_TYPE_HINT_REGEX = '~@param[\s]*([^\n\$\s]*)[\s]*\$([a-zA-Z0-9]*)~';
    private const METHOD_RETURN_TYPE_HINT_REGEX = '~@return[\s]*([^\n\s]*)~';
    private const CLASS_PROPERTY_TYPE_HINT_REGEX = "#@var[\s]*([^\n\$\s]*)#";

    private const SELF_TYPE_HINT = "self";
    private const STATIC_TYPE_HINT = "static";
    private const THIS_TYPE_HINT = '$this';
    private const NULL_HINT = "null";

    private ?AnnotationResolver $annotationParser;

    private function __construct(?AnnotationResolver $annotationParser)
    {
        $this->annotationParser = $annotationParser;
    }

    public static function create(): self
    {
        return new self(null);
    }

    public static function createWithAnnotationParser(AnnotationResolver $annotationParser): self
    {
        return new self($annotationParser);
    }

    public function getMethodParameters(\ReflectionClass $analyzedClass, string $methodName, AnnotationResolver $annotationResolver): iterable
    {
        $parameters = [];
        $reflectionMethod = $analyzedClass->getMethod($methodName);
        $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($analyzedClass, $analyzedClass, $methodName);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = TypeDescriptor::createWithDocBlock(
                $parameter->getType() ? $this->expandParameterTypeHint($this->getTypeFromReflection($parameter->getType()), $analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName)) : null,
                array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : ""
            );
            $isAnnotation = false;
            if ($parameterType->isClassOrInterface() && !$parameterType->isCompoundObjectType() && !$parameterType->isUnionType()) {
                $classDefinition = ClassDefinition::createUsingAnnotationParser($parameterType, $annotationResolver);
                $isAnnotation = $classDefinition->isAnnotation();
            }

            $parameterAttributes = [];
            foreach ($parameter->getAttributes() as $attribute) {
                $parameterAttributes[] = $attribute->newInstance();
            }

            $parameters[] = InterfaceParameter::create(
                $parameter->getName(),
                $parameterType,
                $parameter->getType() ? $parameter->getType()->allowsNull() : true,
                $parameter->isDefaultValueAvailable(),
                $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                $isAnnotation,
                $parameterAttributes
            );
        }

        return $parameters;
    }

    /**
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param string $methodName
     * @return array
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getMethodDocBlockParameterTypeHints(\ReflectionClass $thisClass, \ReflectionClass $analyzedClass, string $methodName): array
    {
        $analyzedClass = $this->getMethodOwnerClass($analyzedClass, $methodName);
        $methodReflection = $analyzedClass->getMethod($methodName);
        $declaringClass = self::getMethodDeclaringClass($analyzedClass, $methodReflection->getName());

        $docComment = $this->getDocComment($analyzedClass, $methodReflection);
        preg_match_all(self::METHOD_DOC_BLOCK_TYPE_HINT_REGEX, $docComment, $matchedDocBlockParameterTypes);

        $docBlockParameterTypeHints = [];
        $matchAmount = count($matchedDocBlockParameterTypes[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $docBlockParameterTypeHints[$matchedDocBlockParameterTypes[2][$matchIndex]] = $this->expandParameterTypeHint($matchedDocBlockParameterTypes[1][$matchIndex], $thisClass, $analyzedClass, $declaringClass);
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
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param string $methodName
     * @return array|\ReflectionClass
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function getMethodOwnerClass(\ReflectionClass $analyzedClass, string $methodName)
    {
        $methodReflection = $analyzedClass->getMethod($methodName);
        $declaringClass = self::getMethodDeclaringClass($analyzedClass, $methodReflection->getName());
        if ($analyzedClass->getName() !== $declaringClass->getName()) {
            return self::getMethodOwnerClass($declaringClass, $methodName);
        }
        foreach ($analyzedClass->getTraits() as $trait) {
            if ($trait->hasMethod($methodReflection->getName()) && !self::wasTraitOverwritten($methodReflection, $trait)) {
                return self::getMethodOwnerClass($trait, $methodName);
            }
        }

        return $analyzedClass;
    }

    private function getDocComment(\ReflectionClass $analyzedClass, \ReflectionMethod $methodReflection): string
    {
        $docComment = $methodReflection->getDocComment();
        if (!$docComment || $this->isIgnoringDocblockTypeHints($analyzedClass, $methodReflection)) {
            return "";
        }

        return $docComment;
    }

    private function isIgnoringDocblockTypeHints(\ReflectionClass $analyzedClass, \ReflectionMethod $methodReflection) : bool
    {
        return (bool)$methodReflection->getAttributes(IgnoreDocblockTypeHint::class) || (bool)$analyzedClass->getAttributes(IgnoreDocblockTypeHint::class);
    }

    private function expandParameterTypeHint(string $parameterTypeHint, \ReflectionClass $thisClass, \ReflectionClass $analyzedClass, \ReflectionClass $declaringClass): string
    {
        $multipleTypeHints = explode("|", $parameterTypeHint);
        $multipleTypeHints = is_array($multipleTypeHints) ? $multipleTypeHints : [$multipleTypeHints];
        $classContents = file_get_contents($analyzedClass->getFileName());
        $statements = array_merge($this->getSingleUseStatements($classContents), $this->getGroupUseStatements($classContents));

        if (!$parameterTypeHint) {
            return TypeDescriptor::ANYTHING;
        }

        $fullNames = [];
        foreach ($multipleTypeHints as $typeHint) {
            if ($typeHint === self::NULL_HINT) {
                continue;
            }
            if (class_exists($typeHint)) {
                $fullNames[] = $typeHint;
                continue;
            }

            if (strpos($typeHint, "[]") !== false) {
                $typeHint = "array<" . str_replace("[]", "", $typeHint) . ">";
            }

            $relatedTypehint = $this->getRelatedClassNameFromTypeHint($typeHint);
            if ($relatedTypehint === self::SELF_TYPE_HINT) {
                $typeHint = str_replace($relatedTypehint, $declaringClass->getName(), $typeHint);
            }else if ($relatedTypehint === self::STATIC_TYPE_HINT) {
                $typeHint = str_replace($relatedTypehint, $analyzedClass->getName(), $typeHint);
            }

            $fullNames[] = $this->isInGlobalNamespace($typeHint)
                ? $typeHint
                : ($this->isFromDifferentNamespace($typeHint, $statements)
                    ? $this->getTypeHintFromUseNamespace($typeHint, $statements)
                    : $this->getWithClassNamespace($thisClass, $analyzedClass, $declaringClass, $typeHint));
        }

        return implode("|", $fullNames);
    }

    private function getGroupUseStatements(string $classContents) : array
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
            list($classNameAlias, $className) = $this->getClassNameAndAlias($foundClass);
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
            list($classNameAlias, $className) = $this->getClassNameAndAlias($className);

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
     * @param string $className
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function isInGlobalNamespace(string $className): bool
    {
        if (in_array($className, [self::SELF_TYPE_HINT, self::STATIC_TYPE_HINT, self::THIS_TYPE_HINT])) {
            return false;
        }

        if (TypeDescriptor::isItTypeOfPrimitive($className) || TypeDescriptor::isItTypeOfVoid($className) || TypeDescriptor::isMixedType($className) || TypeDescriptor::isNull($className)) {
            return true;
        }

        if (TypeDescriptor::isInternalClassOrInterface($className)) {
            return true;
        }

        if (preg_match(self::COLLECTION_TYPE_REGEX, $className, $matches)) {
            return TypeDescriptor::isItTypeOfPrimitive($matches[1]) || TypeDescriptor::isInternalClassOrInterface($matches[1]) || TypeDescriptor::isMixedType($matches[1]);
        }

        return count(explode("\\", $className)) == 2;
    }

    /**
     * @param string $classNameTypeHint
     * @param array $statements
     * @return bool
     */
    private function isFromDifferentNamespace(string $classNameTypeHint, array $statements): bool
    {
        $classNameTypeHint = $this->getRelatedClassNameFromTypeHint($classNameTypeHint);
        foreach ($statements as $classNameWithoutNamespace => $classNameWithNamespace) {
            if ($classNameTypeHint === $classNameWithoutNamespace) {
                return true;
            }
        }

        return
            array_key_exists($classNameTypeHint, $statements) ||
            count(explode("\\", $classNameTypeHint)) > 2;
    }

    /**
     * @param string $classNameTypeHint
     * @return mixed|string
     */
    private function getRelatedClassNameFromTypeHint(string $classNameTypeHint)
    {
        if (strpos($classNameTypeHint, "[]") !== false) {
            $classNameTypeHint = str_replace("[]", "", $classNameTypeHint);
        }
        if (preg_match(TypeDescriptor::COLLECTION_TYPE_REGEX, $classNameTypeHint, $classNameMatch)) {
            $classNameTypeHint = $classNameMatch[1];
        }

        return $classNameTypeHint;
    }

    /**
     * @param string $classNameTypeHint
     * @param array $useStatements
     * @return string
     */
    private function getTypeHintFromUseNamespace(string $classNameTypeHint, array $useStatements): string
    {
        $relatedClassName = $this->getRelatedClassNameFromTypeHint($classNameTypeHint);

        if (array_key_exists($relatedClassName, $useStatements)) {
            return str_replace($relatedClassName, $useStatements[$relatedClassName], $classNameTypeHint);
        }

        return $classNameTypeHint;
    }

    /**
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param \ReflectionClass $declaringClass
     * @param string $parameterTypeHint
     * @return string
     */
    private function getWithClassNamespace(\ReflectionClass $thisClass, \ReflectionClass $analyzedClass, \ReflectionClass $declaringClass, string $parameterTypeHint): string
    {
        if ($parameterTypeHint === self::SELF_TYPE_HINT) {
            return $declaringClass->getName();
        }

        if (in_array($parameterTypeHint, [self::STATIC_TYPE_HINT, self::THIS_TYPE_HINT])) {
            return $thisClass->getName();
        }

        $relatedClassName = $this->getRelatedClassNameFromTypeHint($parameterTypeHint);
        $typeHint = $analyzedClass->getNamespaceName() . "\\" . $relatedClassName;

        if (substr($typeHint, 0, 1) !== "\\") {
            $typeHint = "\\" . $typeHint;
        }

        return str_replace($relatedClassName, $typeHint, $parameterTypeHint);
    }

    /**
     * @param \ReflectionClass $analyzedClass
     * @param string $methodName
     * @return \ReflectionClass
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private static function getMethodDeclaringClass(\ReflectionClass $analyzedClass, string $methodName): \ReflectionClass
    {
        return $analyzedClass->getMethod($methodName)->getDeclaringClass();
    }

    /**
     * @param string $className
     * @return ClassPropertyDefinition[]
     * @throws TypeDefinitionException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getClassProperties(string $className): iterable
    {
        $reflectionClass = new \ReflectionClass($className);
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

    /**
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param \ReflectionClass $declaringClass
     * @param \ReflectionProperty $reflectionProperty
     * @return Type|null
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getPropertyDocblockTypeHint(\ReflectionClass $thisClass, \ReflectionClass $analyzedClass, \ReflectionClass $declaringClass, \ReflectionProperty $reflectionProperty): ?Type
    {
        $docComment = $reflectionProperty->getDocComment();
        preg_match_all(self::CLASS_PROPERTY_TYPE_HINT_REGEX, $docComment, $matchedDocBlockParameterTypes);

        if (!isset($matchedDocBlockParameterTypes[1][0])) {
            return null;
        }

        try {
            $typeDescriptor = TypeDescriptor::create(
                $this->expandParameterTypeHint($matchedDocBlockParameterTypes[1][0], $thisClass, $analyzedClass, $declaringClass)
            );
        }catch (TypeDefinitionException $typeDefinitionException) {
            return null;
        }

        return $typeDescriptor;
    }

    public function getReturnType(\ReflectionClass $analyzedClass, string $methodName): Type
    {
        $reflectionMethod = $analyzedClass->getMethod($methodName);

        return TypeDescriptor::createWithDocBlock(
            $this->expandParameterTypeHint($this->getTypeFromReflection($reflectionMethod->getReturnType()), $analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName)),
            $this->getReturnTypeDocBlockParameterTypeHint($analyzedClass, $analyzedClass, $methodName)
        );
    }

    /**
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param string $methodName
     * @return string
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function getReturnTypeDocBlockParameterTypeHint(\ReflectionClass $thisClass, \ReflectionClass $analyzedClass, string $methodName): ?string
    {
        $analyzedClass = $this->getMethodOwnerClass($analyzedClass, $methodName);
        $methodReflection = $analyzedClass->getMethod($methodName);
        $declaringClass = self::getMethodDeclaringClass($analyzedClass, $methodReflection->getName());

        $docComment = $this->getDocComment($analyzedClass, $methodReflection);
        preg_match(self::METHOD_RETURN_TYPE_HINT_REGEX, $docComment, $matchedDocBlockReturnType);

        if (isset($matchedDocBlockReturnType[1])) {
            return $this->expandParameterTypeHint($matchedDocBlockReturnType[1], $thisClass, $analyzedClass, $declaringClass);
        }

        return null;
    }

    /**
     * @param \ReflectionMethod $methodReflection
     * @param \ReflectionClass $trait
     * @return bool
     * @throws \ReflectionException
     */
    private static function wasTraitOverwritten(\ReflectionMethod $methodReflection, \ReflectionClass $trait): bool
    {
        return $methodReflection->getFileName() !== $trait->getMethod($methodReflection->getName())->getFileName();
    }

    private function createClassProperty(string $declaringClass, AnnotationResolver $annotationParser, \ReflectionProperty $property, ?Type $docblockType): \Ecotone\Messaging\Handler\ClassPropertyDefinition
    {
        $classProperty = null;
        $type = $docblockType ? $docblockType : TypeDescriptor::createAnythingType();
        if ($type->isAnything()) {
            $type = $property->hasType() ? TypeDescriptor::create($this->getTypeFromReflection($property->getType())) : $type;
        }
        $isNullable = $property->hasType() ? $property->getType()->allowsNull() : true;


        $annotations = $annotationParser->getAnnotationsForProperty($declaringClass, $property->getName());
        if ($property->isPrivate()) {
            $classProperty = ClassPropertyDefinition::createPrivate(
                $property->getName(),
                $type,
                $isNullable,
                $property->isStatic(),
                $annotations
            );
        } else if ($property->isProtected()) {
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

    private function createClassPropertyUsingTraitsIfExists(\ReflectionClass $reflectionClassOrTrait, \ReflectionProperty $property, AnnotationResolver $annotationParser, \ReflectionClass $declaringClass)
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

        $type = TypeDescriptor::create($this->getTypeFromReflection($property->getType()));
        return $this->createClassProperty(
                $declaringClass->getName(),
                $annotationParser,
                $property,
            $type && $type->isIterable() && !$type->isCollection()  ? $this->getPropertyDocblockTypeHint($reflectionClassOrTrait, $property->getDeclaringClass(), $property->getDeclaringClass(), $property) : null
        );
    }

    private function getTypeFromReflection(?\ReflectionType $returnType): string
    {
        if ($returnType instanceof \ReflectionUnionType) {
            $types = [];
            foreach ($returnType->getTypes() as $type) {
                $types[] = $type;
            }

            $returnTypeName = implode("|", $types);
        } else {
            $returnTypeName = $returnType ? $returnType->getName() : "";
        }

        return $returnTypeName;
    }

    private function getClassNameAndAlias(mixed $className): array
    {
        $classNameAlias = null;
        if (($alias = explode(" as ", $className)) && $this->hasUseStatementAlias($alias)) {
            $className      = $alias[0];
            $classNameAlias = $alias[1];
        }

        $splittedClassName = explode("\\", $className);
        if ($className[0] !== "\\") {
            $className = "\\" . $className;
        }
        if (!$classNameAlias) {
            $classNameAlias = end($splittedClassName);
        }

        return array($classNameAlias, $className);
    }
}
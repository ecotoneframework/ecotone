<?php

namespace Ecotone\Messaging\Handler;

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
    private const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([^<]*)>/";
    private const CODE_USE_STATEMENTS_REGEX = '/use[\s]*([^;]*)[\s]*;/';

    private const METHOD_DOC_BLOCK_TYPE_HINT_REGEX = '~@param[\s]*([^\n\$\s]*)[\s]*\$([a-zA-Z0-9]*)~';
    private const METHOD_RETURN_TYPE_HINT_REGEX = '~@return[\s]*([^\n\s]*)~';
    private const CLASS_PROPERTY_TYPE_HINT_REGEX = "#@var[\s]*([^\n\$\s]*)#";

    private const SELF_TYPE_HINT = "self";
    private const STATIC_TYPE_HINT = "static";
    private const THIS_TYPE_HINT = '$this';

    /**
     * @var AnnotationParser|null
     */
    private $annotationParser;

    /**
     * TypeResolver constructor.
     * @param AnnotationParser|null $annotationParser
     */
    private function __construct(?AnnotationParser $annotationParser)
    {
        $this->annotationParser = $annotationParser;
    }

    public static function create(): self
    {
        return new self(null);
    }

    /**
     * @param AnnotationParser $annotationParser
     * @return TypeResolver
     */
    public static function createWithAnnotationParser(AnnotationParser $annotationParser): self
    {
        return new self($annotationParser);
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @return InterfaceParameter[]
     * @throws TypeDefinitionException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getMethodParameters(string $interfaceName, string $methodName): iterable
    {
        $analyzedClass = new \ReflectionClass($interfaceName);

        $parameters = [];
        $reflectionMethod = $analyzedClass->getMethod($methodName);
        $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($analyzedClass, $analyzedClass, $methodName);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameters[] = InterfaceParameter::create(
                $parameter->getName(),
                TypeDescriptor::createWithDocBlock(
                    $parameter->getType() ? $this->expandParameterTypeHint($parameter->getType()->getName(), $analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName)) : null,
                    array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : ""
                ),
                $parameter->getType() ? $parameter->getType()->allowsNull() : true
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
        if (self::isInheritDocComment($methodReflection->getDocComment())) {
            if ($analyzedClass->getParentClass() && $analyzedClass->getParentClass()->hasMethod($methodReflection->getName())) {
                return self::getMethodOwnerClass($analyzedClass->getParentClass(), $methodName);
            }
            foreach ($analyzedClass->getInterfaceNames() as $interfaceName) {
                if (method_exists($interfaceName, $methodReflection->getName())) {
                    $reflectionClass = new \ReflectionClass($interfaceName);
                    return self::getMethodOwnerClass($reflectionClass, $methodName);
                }
            }
        }
        foreach ($analyzedClass->getTraits() as $trait) {
            if ($trait->hasMethod($methodReflection->getName()) && !self::wasTraitOverwritten($methodReflection, $trait)) {
                return self::getMethodOwnerClass($trait, $methodName);
            }
        }

        return $analyzedClass;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionMethod $methodReflection
     * @return string
     * @throws \ReflectionException
     */
    private function getDocComment(\ReflectionClass $reflectionClass, \ReflectionMethod $methodReflection): string
    {
        $docComment = $methodReflection->getDocComment();
        if (!$docComment || $this->isIgnoringDocblockTypeHints($docComment)) {
            return "";
        }

        if (preg_match("/@inheritDoc/", $docComment)) {
            if ($reflectionClass->getParentClass() && $reflectionClass->getParentClass()->hasMethod($methodReflection->getName())) {
                $docComment = $reflectionClass->getParentClass()->getMethod($methodReflection->getName())->getDocComment();
            }
            foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
                if (method_exists($interfaceName, $methodReflection->getName())) {
                    $docComment = (new \ReflectionMethod($interfaceName, $methodReflection->getName()))->getDocComment();
                }
            }
        }

        return $docComment;
    }

    /**
     * @param string $docBlock
     * @return bool
     */
    private static function isInheritDocComment(string $docBlock) : bool
    {
        return preg_match("/@inheritDoc/", $docBlock);
    }

    /**
     * @param string $docBlock
     *
     * @return bool
     */
    private function isIgnoringDocblockTypeHints(string $docBlock) : bool
    {
        return preg_match("/@IgnoreDocblockTypeHint/", $docBlock);
    }

    /**
     * @param string $parameterTypeHint
     * @param \ReflectionClass $thisClass
     * @param \ReflectionClass $analyzedClass
     * @param \ReflectionClass $declaringClass
     * @return string
     */
    private function expandParameterTypeHint(string $parameterTypeHint, \ReflectionClass $thisClass, \ReflectionClass $analyzedClass, \ReflectionClass $declaringClass): string
    {
        $multipleTypeHints = explode("|", $parameterTypeHint);
        $multipleTypeHints = is_array($multipleTypeHints) ? $multipleTypeHints : [$multipleTypeHints];
        $statements = $this->getClassUseStatements($analyzedClass);

        if (!$parameterTypeHint) {
            return TypeDescriptor::ANYTHING;
        }

        $fullNames = [];
        foreach ($multipleTypeHints as $typeHint) {
            if (class_exists($typeHint)) {
                $fullNames[] = $typeHint;
                continue;
            }

            if (strpos($typeHint, "[]") !== false) {
                $typeHint = "array<" . str_replace("[]", "", $typeHint) . ">";
            }

            $fullNames[] = $this->isInGlobalNamespace($typeHint)
                ? $typeHint
                : ($this->isFromDifferentNamespace($typeHint, $statements)
                    ? $this->getTypeHintFromUseNamespace($typeHint, $statements)
                    : $this->getWithClassNamespace($thisClass, $analyzedClass, $declaringClass, $typeHint));
        }

        return implode("|", $fullNames);
    }

    /**
     * @param \ReflectionClass $interfaceReflection
     * @return array
     */
    private function getClassUseStatements(\ReflectionClass $interfaceReflection): array
    {
        $code = file_get_contents($interfaceReflection->getFileName());
        preg_match_all(self::CODE_USE_STATEMENTS_REGEX, $code, $foundUseStatements);

        $useStatements = [];
        $matchAmount = count($foundUseStatements[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $className = $foundUseStatements[1][$matchIndex];
            $classNameAlias = null;
            if (($alias = explode(" as ", $className)) && $this->hasUseStatementAlias($alias)) {
                $className = $alias[0];
                $classNameAlias = $alias[1];
            }

            $splittedClassName = explode("\\", $className);
            if ($className[0] !== "\\") {
                $className = "\\" . $className;
            }
            if (!$classNameAlias) {
                $classNameAlias = end($splittedClassName);
            }

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
            return TypeDescriptor::isItTypeOfPrimitive($matches[1]) || TypeDescriptor::isInternalClassOrInterface($matches[1]);
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
            array_key_exists($classNameTypeHint, $statements)
            ||
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
            $classProperties[] = $this->createClassPropertyUsingTraitsIfExists($className, $reflectionClass, $property, $annotationParser);
        }
        while ($parent = $parent->getParentClass()) {
            foreach ($parent->getProperties() as $property) {
                $classProperties[] = $this->createClassPropertyUsingTraitsIfExists($parent, $reflectionClass, $property, $annotationParser);
            }
        }

        return array_unique($classProperties);
    }

    /**
     * @param string $className
     * @return AnnotationParser
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    private function getAnnotationParser(string $className): AnnotationParser
    {
        return $this->annotationParser ? $this->annotationParser : InMemoryAnnotationRegistrationService::createFrom([$className]);
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
            throw TypeDefinitionException::create("There is problem with type of property {$reflectionProperty->getName()} in class {$analyzedClass->getName()}: {$typeDefinitionException}");
        }

        return $typeDescriptor;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @return Type
     * @throws TypeDefinitionException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function getReturnType(string $interfaceName, string $methodName): Type
    {
        $analyzedClass = new \ReflectionClass($interfaceName);
        $reflectionMethod = $analyzedClass->getMethod($methodName);

        $finalType = TypeDescriptor::createWithDocBlock(
            $this->expandParameterTypeHint($reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->getName() : "", $analyzedClass, $analyzedClass, self::getMethodDeclaringClass($analyzedClass, $methodName)),
            $this->getReturnTypeDocBlockParameterTypeHint($analyzedClass, $analyzedClass, $methodName)
        );

        if ($reflectionMethod->getReturnType() && $reflectionMethod->getReturnType()->getName() === TypeDescriptor::VOID && !$finalType->isVoid()) {
            throw InvalidArgumentException::create("Interface {$interfaceName} with method {$methodName} has return type definition in docblock, but declared is void");
        }

        return $finalType;
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

    /**
     * @param string $className
     * @param AnnotationParser $annotationParser
     * @param \ReflectionProperty $property
     * @param Type|null $docblockType
     * @return ClassPropertyDefinition|null
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createClassProperty(string $className, AnnotationParser $annotationParser, \ReflectionProperty $property, ?Type $docblockType)
    {
        $classProperty = null;
        $type = $docblockType ? $docblockType : TypeDescriptor::createAnythingType();
        $isNullable = true;
        if (version_compare(phpversion(), '7.4.0', '>=')) {
            if ($type->isAnything()) {
                $type = $property->hasType() ? TypeDescriptor::create($property->getType()->getName()) : $type;
            }
            $isNullable = $property->hasType() ? $property->getType()->allowsNull() : true;
        }

        $annotations = $annotationParser->getAnnotationsForProperty($className, $property->getName());
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

    /**
     * @param string $className
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionProperty $property
     * @param AnnotationParser $annotationParser
     * @return ClassPropertyDefinition|null
     * @throws TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createClassPropertyUsingTraitsIfExists(string $className, \ReflectionClass $reflectionClass, \ReflectionProperty $property, AnnotationParser $annotationParser)
    {
        foreach ($reflectionClass->getTraits() as $trait) {
            foreach ($trait->getProperties() as $traitProperty) {
                if ($traitProperty->getName() === $property->getName()) {
                    return $this->createClassProperty(
                        $className,
                        $annotationParser,
                        $traitProperty,
                        $this->getPropertyDocblockTypeHint($reflectionClass, $trait, $property->getDeclaringClass(), $traitProperty)
                    );
                }
            }
        }

        return $this->createClassProperty(
                $className,
                $annotationParser,
                $property,
                $this->getPropertyDocblockTypeHint($reflectionClass, $property->getDeclaringClass(), $property->getDeclaringClass(), $property)
        );
    }
}
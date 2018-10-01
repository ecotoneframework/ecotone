<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Future;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCall
{
    private const COLLECTION_TYPE_REGEX = "/[a-zA-Z0-9]*<([^<]*)>/";
    private const CODE_USE_STATEMENTS_REGEX = '/use[\s]*([^;]*)[\s]*;/';
    private const METHOD_DOC_BLOCK_PARAMETERS_REGEX = '~@param[\s]*([^\n\$\s]*)[\s]*\$([a-zA-Z0-9]*)~';
    private const GUESSED_COMPOUND_TYPE_RANK = 3;
    private const GUESSED_SCALAR_TYPE_RANK = 1;
    private const GUESSED_COLLECTION_TYPE_RANK = 4;
    private const GUESSED_CLASS_TYPE_RANK = 2;
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array|InterfaceParameter[]
     */
    private $parameters;
    /**
     * @var string
     */
    private $returnType;
    /**
     * @var bool
     */
    private $doesReturnTypeAllowNulls;
    /**
     * @var bool
     */
    private $isStaticallyCalled;

    /**
     * InterfaceToCall constructor.
     * @param string $interfaceName
     * @param string $methodName
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(string $interfaceName, string $methodName)
    {
        $this->initialize($interfaceName, $methodName);
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName): void
    {
        $reflectionClass = new \ReflectionClass($interfaceName);
        if (!$reflectionClass->hasMethod($methodName)) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
        }

        $parameters = [];
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $docBlockParameterTypeHints = $this->getMethodDocBlockParameterTypeHints($reflectionClass, $reflectionMethod);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameters[] = InterfaceParameter::create(
                $parameter->getName(),
                $parameter->getType() ? $parameter->getType()->getName() : InterfaceParameter::UNKNOWN,
                $parameter->getType() ? $parameter->getType()->allowsNull() : true,
                array_key_exists($parameter->getName(), $docBlockParameterTypeHints) ? $docBlockParameterTypeHints[$parameter->getName()] : ""
            );
        }

        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;
        $this->parameters = $parameters;
        $this->returnType = (string)$reflectionMethod->getReturnType();
        $this->doesReturnTypeAllowNulls = $reflectionMethod->getReturnType() ? $reflectionMethod->getReturnType()->allowsNull() : true;
        $this->isStaticallyCalled = $reflectionMethod->isStatic();
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param \ReflectionMethod $interfaceReflection
     * @return array
     */
    private function getMethodDocBlockParameterTypeHints(\ReflectionClass $reflectionClass, \ReflectionMethod $interfaceReflection) : array
    {
        $statements = $this->getClassUseStatements($reflectionClass);
        if (!$interfaceReflection->getDocComment()) {
            return [];
        }

        preg_match_all(self::METHOD_DOC_BLOCK_PARAMETERS_REGEX, $interfaceReflection->getDocComment(), $matchedDocBlockParameterTypes);

        $docBlockParameterTypeHints = [];
        $matchAmount = count($matchedDocBlockParameterTypes[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $parameterTypeHint = $this->guessParameterTypeHint($matchedDocBlockParameterTypes[1][$matchIndex]);

            $docBlockParameterTypeHints[$matchedDocBlockParameterTypes[2][$matchIndex]] =
                $this->isInGlobalNamespace($parameterTypeHint)
                    ? $parameterTypeHint
                    : ($this->isFromDifferentNamespace($parameterTypeHint, $statements)
                        ? $this->getTypeHintFromUseNamespace($parameterTypeHint, $statements)
                        : $this->getWithClassNamespace($reflectionClass, $parameterTypeHint));
        }

        return $docBlockParameterTypeHints;
    }

    /**
     * @param string $classNameTypeHint
     * @param array $useStatements
     * @return string
     */
    private function getTypeHintFromUseNamespace(string $classNameTypeHint, array $useStatements) : string
    {
        $relatedClassName = $this->getRelatedClassNameFromTypeHint($classNameTypeHint);
        if (array_key_exists($relatedClassName, $useStatements)) {
            return str_replace($relatedClassName, $useStatements[$relatedClassName], $classNameTypeHint);
        }

        return $classNameTypeHint;
    }

    /**
     * @param \ReflectionClass $interfaceReflection
     * @return array
     */
    private function getClassUseStatements(\ReflectionClass $interfaceReflection) : array
    {
        $code = file_get_contents($interfaceReflection->getFileName());
        preg_match_all(self::CODE_USE_STATEMENTS_REGEX, $code, $foundUseStatements);

        $useStatements = [];
        $matchAmount = count($foundUseStatements[0]);
        for ($matchIndex = 0; $matchIndex < $matchAmount; $matchIndex++) {
            $className = $foundUseStatements[self::GUESSED_SCALAR_TYPE_RANK][$matchIndex];
            $classNameAlias = null;
            if (($alias = explode(" as ", $className)) && $this->hasUseStatementAlias($alias)) {
                $className = $alias[0];
                $classNameAlias = $alias[self::GUESSED_SCALAR_TYPE_RANK];
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
     * @param string $interfaceName
     * @param string $methodName
     * @return InterfaceToCall
     */
    public static function create(string $interfaceName, string $methodName): self
    {
        return new self($interfaceName, $methodName);
    }

    /**
     * @param $object
     * @param string $methodName
     * @return InterfaceToCall
     * @throws InvalidArgumentException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createFromObject($object, string $methodName): self
    {
        Assert::isObject($object, "Passed value to InterfaceToCall is not object");

        return new self(get_class($object), $methodName);
    }

    /**
     * @param string|object $unknownType
     * @param string $methodName
     *
     * @return InterfaceToCall
     */
    public static function createFromUnknownType($unknownType, string $methodName) : self
    {
        if (is_object($unknownType)) {
            return self::createFromObject($unknownType, $methodName);
        }

        return self::create($unknownType, $methodName);
    }

    /**
     * @return bool
     */
    public function isStaticallyCalled() : bool
    {
        return $this->isStaticallyCalled;
    }

    /**
     * @return bool
     */
    public function hasReturnValue() : bool
    {
        return !($this->getReturnType() == 'void');
    }

    /**
     * @return bool
     */
    public function hasReturnTypeVoid() : bool
    {
        return $this->getReturnType() == 'void';
    }

    /**
     * @return bool
     */
    public function hasReturnValueBoolean() : bool
    {
        return $this->getReturnType() == "bool";
    }

    /**
     * @return bool
     */
    public function doesItReturnArray() : bool
    {
        return $this->getReturnType() == "array";
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFirstParameterName(): string
    {
        return $this->getFirstParameter()->getName();
    }

    /**
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFirstParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return $this->getParameters()[0];
    }

    /**
     * @param int $index
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getParameterAtIndex(int $index) : InterfaceParameter
    {
        if (!array_key_exists($index, $this->getParameters())) {
            throw InvalidArgumentException::create("There is no parameter at index {$index} for {$this}");
        }

        return $this->parameters[$index];
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->getParameters());
    }

    /**
     * @return array|InterfaceParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function hasFirstParameterMessageTypeHint(): bool
    {
        return $this->getFirstParameter()->isMessage();
    }

    /**
     * @return bool
     */
    public function doesItReturnFuture(): bool
    {
        return $this->getReturnType() == Future::class;
    }

    /**
     * @return bool
     */
    public function isReturnTypeUnknown(): bool
    {
        $returnType = $this->getReturnType();

        return is_null($returnType) || $returnType === '';
    }

    /**
     * @return bool
     */
    public function doesItReturnMessage() : bool
    {
        return $this->getReturnType() === Message::class || is_subclass_of($this->getReturnType(), Message::class);
    }

    /**
     * @return string
     */
    public function getMethodName() : string
    {
        return $this->methodName;
    }

    /**
     * @return string
     */
    public function getInterfaceName() : string
    {
        return $this->interfaceName;
    }

    /**
     * @return bool
     */
    public function canItReturnNull(): bool
    {
        return is_null($this->returnType) || $this->doesReturnTypeAllowNulls;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFirstParameterTypeHint() : string
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Trying to get first parameter, but has none");
        }

        return $this->getFirstParameter()->getTypeHint();
    }

    /**
     * @param string $parameterName
     *
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getParameterWithName(string $parameterName): InterfaceParameter
    {
        foreach ($this->getParameters() as $parameter) {
            if ($parameter->getName() == $parameterName) {
                return $parameter;
            }
        }

        throw InvalidArgumentException::create($this . " doesn't have parameter with name {$parameterName}");
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneParameter(): bool
    {
        return $this->parameterAmount() > 1;
    }

    /**
     * @return bool
     */
    public function hasNoParameters() : bool
    {
        return $this->parameterAmount() == 0;
    }

    /**
     * @return bool
     */
    public function hasSingleArgument(): bool
    {
        return $this->parameterAmount() == 1;
    }

    /**
     * @return string
     */
    private function getReturnType(): string
    {
        return $this->returnType;
    }

    public function __toString()
    {
        return "Interface {$this->interfaceName} with method {$this->methodName}";
    }

    /**
     * @param string $className
     * @return bool
     */
    private function isInGlobalNamespace(string $className): bool
    {
        if (InterfaceParameter::isPrimitiveType($className)) {
            return true;
        }

        if (preg_match(self::COLLECTION_TYPE_REGEX, $className, $matches)) {
            return InterfaceParameter::isScalar($matches[1]);
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
        if (preg_match(self::COLLECTION_TYPE_REGEX, $classNameTypeHint, $classNameMatch)) {
            $classNameTypeHint = $classNameMatch[1];
        }

        return $classNameTypeHint;
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
     * @param string $parameterTypeHint
     * @return string
     */
    private function guessParameterTypeHint(string $parameterTypeHint) : string
    {
        $multipleTypeHints = explode("|", $parameterTypeHint);
        $multipleTypeHints = is_array($multipleTypeHints) ? $multipleTypeHints : [$multipleTypeHints];

        $typeHintRank = 0;
        foreach ($multipleTypeHints as $typeHint) {
            if (strpos($typeHint, "[]") !== false) {
                $typeHint = "array<" . str_replace("[]", "", $typeHint) . ">";
            }

            if (InterfaceParameter::isScalar($typeHint) && $typeHintRank < self::GUESSED_SCALAR_TYPE_RANK) {
                $parameterTypeHint = $typeHint;
                $typeHintRank = self::GUESSED_SCALAR_TYPE_RANK;
            }else if (InterfaceParameter::isCompoundType($typeHint) && $typeHintRank < self::GUESSED_COMPOUND_TYPE_RANK) {
                $parameterTypeHint = $typeHint;
                $typeHintRank = self::GUESSED_COMPOUND_TYPE_RANK;
            } else if (preg_match(self::COLLECTION_TYPE_REGEX, $typeHint) && $typeHintRank < self::GUESSED_COLLECTION_TYPE_RANK) {
                $parameterTypeHint = $typeHint;
                $typeHintRank = self::GUESSED_COLLECTION_TYPE_RANK;
            }else if ($typeHintRank < 1) {
                $parameterTypeHint = $typeHint;
                $typeHintRank = self::GUESSED_SCALAR_TYPE_RANK;
            }
        }

        return $parameterTypeHint;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param $parameterTypeHint
     * @return string
     */
    private function getWithClassNamespace(\ReflectionClass $reflectionClass, $parameterTypeHint): string
    {
        $typeHint = $reflectionClass->getNamespaceName() . "\\" . $parameterTypeHint;

        if (substr( $typeHint, 0, 1) !== "\\") {
            $typeHint = "\\" . $typeHint;
        }

        return $typeHint;
    }
}
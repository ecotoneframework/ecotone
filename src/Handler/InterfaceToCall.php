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
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameters[] = InterfaceParameter::create(
                $parameter->getName(),
                $parameter->getType() ? $parameter->getType()->getName() : InterfaceParameter::UNKNOWN,
                $parameter->getType() ? $parameter->getType()->allowsNull() : true,
                []
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
}
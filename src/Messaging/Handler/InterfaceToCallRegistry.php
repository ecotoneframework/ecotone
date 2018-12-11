<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Class InterfaceToCallBuilder
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallRegistry
{
    const REFERENCE_NAME = "interfaceToCallRegistry";

    /**
     * @var InterfaceToCall[]
     */
    private $interfacesToCall = [];

    /**
     * InterfaceToCallRegistry constructor.
     * @param InterfaceToCall[] $interfacesToCall
     */
    private function __construct(array $interfacesToCall)
    {
        foreach ($interfacesToCall as $interfaceToCall) {
            $this->interfacesToCall[$this->getName($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName())] = $interfaceToCall;
        }
    }

    /**
     * @param array $interfacesToCall
     * @return InterfaceToCallRegistry
     */
    public static function createWith(array $interfacesToCall) : self
    {
        return new self($interfacesToCall);
    }

    /**
     * @return InterfaceToCallRegistry
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return InterfaceToCall
     */
    public function getFor($interfaceName, string $methodName) : InterfaceToCall
    {
        if (array_key_exists($this->getName($interfaceName, $methodName), $this->interfacesToCall)) {
            return $this->interfacesToCall[$this->getName($interfaceName, $methodName)];
        }

        $interfaceToCall = InterfaceToCall::createFromUnknownType($interfaceName, $methodName);
        $this->interfacesToCall[$this->getName($interfaceName, $methodName)] = $interfaceToCall;

        return $interfaceToCall;
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return string
     */
    private function getName($interfaceName, string $methodName) : string
    {
        if (is_object($interfaceName)) {
            $interfaceName = get_class($interfaceName);
        }

        return $interfaceName . $methodName;
    }
}
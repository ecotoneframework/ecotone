<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;

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
     * @var AnnotationParser
     */
    private $annotationParser;

    /**
     * InterfaceToCallRegistry constructor.
     * @param AnnotationParser $annotationParser
     */
    private function __construct(AnnotationParser $annotationParser)
    {
        $this->annotationParser = $annotationParser;
    }

    /**
     * @return InterfaceToCallRegistry
     */
    public static function createEmpty() : self
    {
        return new self(InMemoryAnnotationRegistrationService::createEmpty());
    }

    /**
     * @param AnnotationParser $annotationParser
     * @return InterfaceToCallRegistry
     */
    public static function createWith(AnnotationParser $annotationParser) : self
    {
        return new self($annotationParser);
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

        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName, $this->annotationParser);
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
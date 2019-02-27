<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Aspect;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\MethodArgument;

/**
 * Class AspectRegistry
 * @package SimplyCodedSoftware\Messaging\Aspect
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AspectCallingFactory
{
    /**
     * @var InterfaceToCallRegistry
     */
    private $interfaceToCallRegistry;

    /**
     * AspectCallingFactory constructor.
     * @param InterfaceToCallRegistry $interfaceToCallRegistry
     */
    public function __construct(InterfaceToCallRegistry $interfaceToCallRegistry)
    {
        $this->interfaceToCallRegistry = $interfaceToCallRegistry;
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param \SimplyCodedSoftware\Messaging\Handler\MethodArgument[]
     *
     * @return AspectReadyExecution
     */
    public function prepare($object, string $methodName, array $methodArguments) : AspectReadyExecution
    {

    }
}
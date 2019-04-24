<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\MethodArgument;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodCall
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCall
{
    /**
     * @var MethodArgument[]
     */
    private $methodArguments;

    /**
     * MethodCall constructor.
     * @param MethodArgument[] $methodArguments
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(array $methodArguments)
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);
        $this->methodArguments = $methodArguments;
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @return MethodCall
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(array $methodArguments) : self
    {
        return new self($methodArguments);
    }

    /**
     * @param TypeDescriptor $typeHint
     * @return MethodArgument[]
     */
    public function getArgumentsWithTypeHint(TypeDescriptor $typeHint) : array
    {
        $methodArguments = [];
        foreach ($this->methodArguments as $methodArgument) {
            if ($methodArgument->hasTypeHint($typeHint)) {
                $methodArguments[] = $methodArgument;
            }
        }

        return $methodArguments;
    }

    /**
     * @param string $parameterName
     * @param mixed $value
     */
    public function replaceArgument(string $parameterName, $value) : void
    {
        $this->methodArguments = array_map(function (MethodArgument $methodArgument) use ($parameterName, $value) {
            if ($methodArgument->getParameterName() == $parameterName) {
                return $methodArgument->replaceValue($value);
            }

            return $methodArgument;
        }, $this->methodArguments);
    }

    /**
     * @return MethodArgument[]|iterable
     */
    public function getMethodArguments() : iterable
    {
        return $this->methodArguments;
    }

    /**
     * @return array
     */
    public function getMethodArgumentValues() : array
    {
        return array_map(function (MethodArgument $argument){
            return $argument->value();
        }, $this->methodArguments);
    }

    /**
     * @param string $parameterName
     * @return bool
     */
    public function hasMethodArgumentWithName(string $parameterName): bool
    {
        foreach ($this->methodArguments as $methodArgument) {
            if ($methodArgument->getParameterName() === $parameterName) {
                return true;
            }
        }

        return false;
    }
}
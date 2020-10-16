<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MethodCall
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodCall
{
    /**
     * @var MethodArgument[]
     */
    private array $methodArguments;
    private bool $canReplaceArguments;

    /**
     * MethodCall constructor.
     * @param MethodArgument[] $methodArguments
     * @param bool $canReplaceArguments
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(array $methodArguments, bool $canReplaceArguments)
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);
        $this->methodArguments = $methodArguments;
        $this->canReplaceArguments = $canReplaceArguments;
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @param bool $canReplaceArguments
     * @return MethodCall
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWith(array $methodArguments, bool $canReplaceArguments) : self
    {
        return new self($methodArguments, $canReplaceArguments);
    }

    /**
     * @param string $parameterName
     * @param mixed $value
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function replaceArgument(string $parameterName, $value) : void
    {
        if (!$this->canReplaceArguments) {
            throw InvalidArgumentException::create("Gateways, Inbound Adapters, Pollable Consumers can't replace arguments in Around Interceptors");
        }

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
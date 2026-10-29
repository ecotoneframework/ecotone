<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MessageToStaticValueParameterConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class ValueConverter implements ParameterConverter
{
    public function __construct(private mixed $staticValue)
    {
    }

    public static function createWith(mixed $staticValue): self
    {
        return new self($staticValue);
    }

    public static function fromConfigurationVariableService(ConfigurationVariableService $configurationVariableService, string $variableName, bool $isRequired, mixed $defaultValue): self
    {
        if ($configurationVariableService->hasName($variableName)) {
            $variableValue = $configurationVariableService->getByName($variableName);
        } elseif (! $isRequired) {
            $variableValue = $defaultValue;
        } else {
            throw InvalidArgumentException::create('Trying to access configuration variable `' . $variableName . "` however it's missing");
        }
        return new self($variableValue);
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message): mixed
    {
        return $this->staticValue;
    }
}

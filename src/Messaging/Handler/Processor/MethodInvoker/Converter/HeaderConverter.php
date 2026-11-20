<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Type;

/**
 * Class HeaderArgument
 * @package Ecotone\Messaging\Handler\ServiceActivator
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class HeaderConverter implements ParameterConverter
{
    public function __construct(private ?Type $parameterType, private ?ParameterDefaultValue $defaultValue, private string $headerName, private bool $isRequired, private ArgumentTypeConversion $argumentTypeConversion)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        if (! $message->getHeaders()->containsKey($this->headerName)) {
            if ($this->defaultValue) {
                return $this->defaultValue->getValue();
            }

            if (! $this->isRequired) {
                return;
            }
        }

        $headerValue = $message->getHeaders()->get($this->headerName);

        if (! $this->parameterType->accepts($headerValue) && ! $this->argumentTypeConversion->canConvert($headerValue, $this->parameterType)) {
            $sourceValueType = Type::createFromVariable($headerValue);

            throw ConversionException::create("Can't convert {$this->headerName} from {$sourceValueType} to {$this->parameterType}. Lack of PHP Converter or JSON Media Type Converter available.");
        }

        return $this->argumentTypeConversion->convertWhenPossible($headerValue, $this->parameterType);
    }
}

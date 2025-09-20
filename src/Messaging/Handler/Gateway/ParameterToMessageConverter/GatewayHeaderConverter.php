<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class HeaderMessageParameter
 * @package Ecotone\Messaging\Handler\Gateway\Gateway\MethodParameterConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class GatewayHeaderConverter implements GatewayParameterConverter
{
    private string $parameterName;
    private string $headerName;

    /**
     * HeaderMessageParameter constructor.
     * @param string $parameterName
     * @param string $headerName
     */
    public function __construct(string $parameterName, string $headerName)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return GatewayHeaderConverter
     */
    public static function create(string $parameterName, string $headerName): self
    {
        return new self($parameterName, $headerName);
    }

    /**
     * @inheritDoc
     */
    public function isSupporting(?MethodArgument $methodArgument): bool
    {
        return $methodArgument && ($this->parameterName === $methodArgument->getParameterName());
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder
    {
        Assert::notNull($methodArgument, 'Gateway header converter can only be called with method argument');
        return $messageBuilder
                    ->setHeader($this->headerName, $methodArgument->value());
    }
}

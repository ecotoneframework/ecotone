<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadConverter;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionConverter;
use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class MethodCallToMessageConverter
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MethodCallToMessageConverter
{
    /**
     * @param array|GatewayParameterConverter[] $methodArgumentConverters
     * @param string[] $parameterNames
     */
    public function __construct(private array $methodArgumentConverters, private array $parameterNames, private EcotoneClockInterface $clock)
    {
        Assert::allInstanceOfType($methodArgumentConverters, GatewayParameterConverter::class);
    }

    public function convert(array $methodArgumentValues): MessageBuilder
    {
        $methodArguments = [];
        for ($index = 0; $index < count($methodArgumentValues); $index++) {
            $methodValue = $methodArgumentValues[$index];
            $methodArguments[] = MethodArgument::createWith($this->parameterNames[$index], $methodValue);
        }
        return $this->convertFor($this->getMessageBuilderUsingPayloadConverter($methodArguments), $methodArguments);
    }

    /**
     * @param MethodArgument[] $methodArguments
     */
    private function convertFor(MessageBuilder $messageBuilder, array $methodArguments): MessageBuilder
    {
        Assert::allInstanceOfType($methodArguments, MethodArgument::class);

        foreach ($this->methodArgumentConverters as $methodParameterConverter) {
            if (empty($methodArguments) &&  ! $this->isPayloadConverter($methodParameterConverter) && $methodParameterConverter->isSupporting(null)) {
                $messageBuilder = $methodParameterConverter->convertToMessage(null, $messageBuilder);
                continue;
            }

            foreach ($methodArguments as $methodArgument) {
                if (! $this->isPayloadConverter($methodParameterConverter) && $methodParameterConverter->isSupporting($methodArgument)) {
                    $messageBuilder = $methodParameterConverter->convertToMessage($methodArgument, $messageBuilder);
                    break;
                }
            }
        }

        return $messageBuilder;
    }

    /**
     * @param MethodArgument[] $methodArguments
     * @return MessageBuilder
     */
    public function getMessageBuilderUsingPayloadConverter(array $methodArguments): MessageBuilder
    {
        $defaultBuilder = MessageBuilder::withPayload('')
            ->setHeader(MessageHeaders::TIMESTAMP, $this->clock->now()->unixTime()->inSeconds());
        foreach ($methodArguments as $methodArgument) {
            foreach ($this->methodArgumentConverters as $methodParameterConverter) {
                if ($this->isPayloadConverter($methodParameterConverter) && $methodParameterConverter->isSupporting($methodArgument)) {
                    return $methodParameterConverter->convertToMessage($methodArgument, $defaultBuilder);
                }
            }
        }

        return $defaultBuilder;
    }

    private function isPayloadConverter(GatewayParameterConverter $methodParameterConverter): bool
    {
        return ($methodParameterConverter instanceof GatewayPayloadConverter || $methodParameterConverter instanceof GatewayPayloadExpressionConverter);
    }
}

<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Message;

/**
 * Class MessageToExpressionEvaluationConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PayloadExpressionConverter implements ParameterConverter
{
    public function __construct(private ReferenceSearchService $referenceSearchService, private ExpressionEvaluationService $expressionEvaluationService, private string $expression)
    {
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(Message $message)
    {
        return $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                'value' => $message->getPayload(),
                'headers' => $message->getHeaders()->headers(),
                'payload' => $message->getPayload(),
            ],
            $this->referenceSearchService
        );
    }
}

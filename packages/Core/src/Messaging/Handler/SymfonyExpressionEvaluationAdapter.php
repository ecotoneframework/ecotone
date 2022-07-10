<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class SymfonyExpressionEvaluationAdapter
 * @package Test\Ecotone\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SymfonyExpressionEvaluationAdapter implements ExpressionEvaluationService
{
    private ExpressionLanguage $language;

    /**
     * SymfonyExpressionEvaluationAdapter constructor.
     *
     * @param ExpressionLanguage $expressionLanguage
     */
    private function __construct(ExpressionLanguage $expressionLanguage)
    {
        $expressionLanguage->register(
            'extract', function ($str) {
            return $str;
        }, function ($arguments, array $payload, string $expression, bool $unique = true) use ($expressionLanguage) {
            $extractedValues = [];
            foreach ($payload as $item) {
                $extractedValues[] = $expressionLanguage->evaluate($expression, $item);
            }

            if (!$unique) {
                return $extractedValues;
            }

            return array_unique($extractedValues);
        }
        );

        $expressionLanguage->register(
            'each', function ($str) {
            return $str;
        }, function ($arguments, array $payload, string $expression) use ($expressionLanguage) {
            $transformedElements = [];
            foreach ($payload as $item) {
                $transformedElements[] = $expressionLanguage->evaluate($expression, array_merge(["element" => $item], $arguments));
            }

            return $transformedElements;
        }
        );

        $expressionLanguage->register(
            'createArray', function ($str) {
            return $str;
        }, function ($arguments, string $key, $value) {
            return [
                $key => $value
            ];
        }
        );

        $expressionLanguage->register(
            'isArray', function ($str) {
            return $str;
        }, function ($arguments, $value) {
            return is_array($value);
        }
        );

        $expressionLanguage->register(
            'isset', function ($str) {
            return $str;
        }, function ($arguments, array $array, string $key) {
            return isset($array[$key]);
        }
        );

        $expressionLanguage->register(
            'reference', function ($str) {
            return $str;
        }, function ($arguments, string $referenceName) use ($expressionLanguage) {
            return $expressionLanguage->evaluate("referenceService.get('{$referenceName}')", $arguments);
        }
        );

        $this->language = $expressionLanguage;
    }

    public static function create(): ExpressionEvaluationService
    {
        if (!class_exists(ExpressionLanguage::class)) {
            return new StubExpressionEvaluationAdapter();
        }

        return new self(new ExpressionLanguage());
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string $expression, array $evaluationContext, ReferenceSearchService $referenceSearchService)
    {
        return $this->language->evaluate($expression, array_merge($evaluationContext, ["referenceService" => $referenceSearchService]));
    }
}
<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Class SymfonyExpressionEvaluationAdapter
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SymfonyExpressionEvaluationAdapter implements \SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService
{
    /**
     * @var ExpressionLanguage
     */
    private $language;

    /**
     * SymfonyExpressionEvaluationAdapter constructor.
     *
     * @param ExpressionLanguage $expressionLanguage
     */
    private function __construct(ExpressionLanguage $expressionLanguage)
    {
        $expressionLanguage->register('extract', function ($str) {
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
        });

        $expressionLanguage->register('each', function ($str) {
            return $str;
        }, function ($arguments, array $payload, string $expression) use ($expressionLanguage) {
            $transformedElements = [];
            foreach ($payload as $item) {
                $transformedElements[] = $expressionLanguage->evaluate($expression, ["element" => $item]);
            }

            return $transformedElements;
        });

        $expressionLanguage->register('createArray', function ($str) {
            return $str;
        }, function ($arguments, string $key, $value) use ($expressionLanguage) {
            return [
                $key => $value
            ];
        });

        $expressionLanguage->register('isArray', function ($str) {
            return $str;
        }, function ($arguments, $value) use ($expressionLanguage) {
            return is_array($value);
        });

        $expressionLanguage->register('isset', function ($str) {
            return $str;
        }, function ($arguments, array $array, string $key) use ($expressionLanguage) {
            return isset($array[$key]);
        });

        $this->language = $expressionLanguage;
    }

    /**
     * @return SymfonyExpressionEvaluationAdapter
     */
    public static function create() : self
    {
        return new self(new ExpressionLanguage());
    }

    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @return SymfonyExpressionEvaluationAdapter
     */
    public static function createWithExternalExpressionLanguage(ExpressionLanguage $expressionLanguage) : self
    {
        return new self($expressionLanguage);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string $expression, array $evaluationContext)
    {
        return $this->language->evaluate($expression, $evaluationContext);
    }
}
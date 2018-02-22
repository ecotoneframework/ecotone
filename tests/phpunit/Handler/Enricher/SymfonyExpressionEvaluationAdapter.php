<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\ExpressionEvaluationService;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class SymfonyExpressionEvaluationAdapter
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SymfonyExpressionEvaluationAdapter implements ExpressionEvaluationService
{
    /**
     * @var ExpressionLanguage
     */
    private $language;

    private function __construct()
    {
        $this->language = new ExpressionLanguage();
    }

    public static function create() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function evaluate(string $expression, array $evaluationContext)
    {
        return $this->language->evaluate($expression, $evaluationContext);
    }
}
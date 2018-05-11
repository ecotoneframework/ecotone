<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;

/**
 * Class SymfonyExpressionEvaluationAdapterTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SymfonyExpressionEvaluationAdapterTest extends TestCase
{
    public function test_is_array_function()
    {
        $expressionLanguage = SymfonyExpressionEvaluationAdapter::create();

        $this->assertTrue($expressionLanguage->evaluate("isArray([])", []));
        $this->assertFalse($expressionLanguage->evaluate("isArray('some')", []));
    }
}
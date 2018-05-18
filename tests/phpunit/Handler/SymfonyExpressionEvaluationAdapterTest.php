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

    public function test_do_for_each_element_in_array()
    {
        $expressionLanguage = SymfonyExpressionEvaluationAdapter::create();

        $this->assertEquals(
            [
                [
                    "id" => 1
                ],
                [
                    "id" => 2
                ],
                [
                    "id" => 3
                ]
            ],
            $expressionLanguage->evaluate("each(payload, 'createArray(\'id\', element)')", ["payload" => [1, 2, 3]])
        );
    }
}
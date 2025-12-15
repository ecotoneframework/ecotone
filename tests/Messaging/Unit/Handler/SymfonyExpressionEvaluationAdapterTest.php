<?php

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class SymfonyExpressionEvaluationAdapterTest
 * @package Test\Ecotone\Messaging\Unit\Handler
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class SymfonyExpressionEvaluationAdapterTest extends TestCase
{
    public function test_is_array_function()
    {
        $expressionLanguage = SymfonyExpressionEvaluationAdapter::create(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue($expressionLanguage->evaluate('isArray([])', [], InMemoryReferenceSearchService::createEmpty()));
        $this->assertFalse($expressionLanguage->evaluate("isArray('some')", [], InMemoryReferenceSearchService::createEmpty()));
    }

    public function test_do_for_each_element_in_array()
    {
        $expressionLanguage = SymfonyExpressionEvaluationAdapter::create(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals(
            [
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
                [
                    'id' => 3,
                ],
            ],
            $expressionLanguage->evaluate("each(payload, 'createArray(\'id\', element)')", ['payload' => [1, 2, 3]], InMemoryReferenceSearchService::createEmpty())
        );
    }

    public function test_parameter_function_in_expression()
    {
        $handler = new class () {
            public int $result = 0;

            #[CommandHandler('calculate')]
            public function handle(#[Payload("parameter('multiplier') * payload['value']")] int $calculatedValue): void
            {
                $this->result = $calculatedValue;
            }

            #[QueryHandler('getResult')]
            public function getResult(): int
            {
                return $this->result;
            }
        };

        $messaging = EcotoneLite::bootstrapFlowTesting(
            [get_class($handler)],
            [$handler],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            configurationVariables: [
                'multiplier' => 10,
            ]
        );

        $messaging->sendCommandWithRoutingKey('calculate', ['value' => 5]);

        $this->assertEquals(50, $messaging->sendQueryWithRouting('getResult'));
    }
}

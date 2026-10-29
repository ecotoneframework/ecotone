<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Gateway\Converter\Serializer;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleSingleConverterService;

/**
 * Class SerializerModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class SerializerModuleTest extends AnnotationConfigurationTestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_converting_from_php()
    {
        $messagingSystem = EcotoneLite::bootstrapFlowTesting([ExampleSingleConverterService::class], [new ExampleSingleConverterService()]);
        /** @var Serializer $gateway */
        $gateway = $messagingSystem->getGateway(Serializer::class);

        $this->assertEquals(
            new stdClass(),
            $gateway->convertFromPHP('test', MediaType::createApplicationXPHPWithTypeParameter(stdClass::class)->toString())
        );
    }

    public function test_converting_to_php()
    {
        $messagingSystem = EcotoneLite::bootstrapFlowTesting([ExampleSingleConverterService::class], [new ExampleSingleConverterService()]);

        /** @var Serializer $gateway */
        $gateway = $messagingSystem->getGateway(Serializer::class);

        $this->assertEquals(
            new stdClass(),
            $gateway->convertToPHP('test', MediaType::APPLICATION_X_PHP, stdClass::class)
        );
    }
}

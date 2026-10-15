<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ServiceActivator;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Fixture\Service\ServiceReturningMessage;
use Test\Ecotone\Messaging\Fixture\Service\StaticallyCalledService;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * ServiceActivatorBuilder::withPassThroughMessageOnVoidInterface() has no
 * #[InternalHandler] attribute equivalent -- the two tests below stay here.
 * The interceptor chain, array-return, and changing-headers behaviour is
 * covered via the real attribute in ServiceActivatorAttributeTest.
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ServiceActivatorBuilderTest extends MessagingTestCase
{
    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_building_service_activator()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceExpectingOneArgument::class], [ServiceExpectingOneArgument::class => $objectToInvoke], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotoneLite->sendDirectToChannel('withoutReturnValue', 'some');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_handler_returns_message_with_no_reply_channel_and_making_use_of_requested_reply_channel()
    {
        $message = MessageBuilder::withPayload('some')
                    ->build();

        $objectToInvoke = ServiceReturningMessage::createWith($message);
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceReturningMessage::class], [ServiceReturningMessage::class => $objectToInvoke], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('get', 'someOther')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_activating_statically_called_service()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([StaticallyCalledService::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $this->assertNotNull(
            $ecotoneLite->sendDirectToChannel('run', 'someOther')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_calling_direct_object_reference()
    {
        $objectToInvoke = ServiceExpectingOneArgument::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting([ServiceExpectingOneArgument::class], [ServiceExpectingOneArgument::class => $objectToInvoke], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));

        $ecotoneLite->sendDirectToChannel('withoutReturnValue', 'some');

        $this->assertTrue($objectToInvoke->wasCalled());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_through_on_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withoutReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build();

        // value is returned even so service is void
        $this->assertEquals(
            'test',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
        ;
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_ignoring_passing_through_when_service_not_void()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(ServiceExpectingOneArgument::create(), 'withReturnValue')
                    ->withInputChannelName('inputChannel')
                    ->withPassThroughMessageOnVoidInterface(true)
            )
            ->build(
            );

        $this->assertEquals(
            'test_called',
            $messaging->sendDirectToChannel('inputChannel', 'test')
        );
        ;
    }
}

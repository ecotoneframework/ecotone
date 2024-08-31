<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Test\LoggerExample;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\Merchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantSubscriber;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\User;

/**
 * Class LoggingHandlerBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class LoggingHandlerBuilderTest extends MessagingTest
{
    public function test_logging_during_sending()
    {
        $messaging = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, User::class, MerchantSubscriber::class],
            [
                new MerchantSubscriber(),
                LoggingHandlerBuilder::LOGGER_REFERENCE => $logger = LoggerExample::create(),
            ]
        );

        $this->assertEmpty($logger->getInfo());

        $messaging->sendCommand(new CreateMerchant('123'));

        $this->assertNotEmpty($logger->getInfo());
    }
}

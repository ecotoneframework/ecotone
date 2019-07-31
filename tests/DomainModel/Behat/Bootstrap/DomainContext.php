<?php

namespace Test\Ecotone\DomainModel\Behat\Bootstrap;

require_once __DIR__ . "/../../../TestBootstrap.php";

use Behat\Behat\Context\Context;
use PHPUnit\Framework\TestCase;
use Ecotone\DomainModel\CommandBus;
use Ecotone\DomainModel\QueryBus;
use Ecotone\Messaging\Conversion\MediaType;
use Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\Ecotone\Messaging\Behat\Bootstrap\AnnotationBasedMessagingContext;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @Given I have order with id :orderId for :productAmount products registered to shipping address :shippingAddress
     *
     * @param int $orderId
     * @param int $productAmount
     * @param string $shippAddress
     */
    public function iHaveOrderWithIdForProductsRegisteredToShippingAddress(int $orderId, int $productAmount, string $shippAddress)
    {
        AnnotationBasedMessagingContext::getCommandBus()->send(CreateOrderCommand::createWith($orderId, $productAmount, $shippAddress));
    }

    /**
     * @When I change order with id of :orderId the shipping address to :shippingAddress
     *
     * @param int $orderId
     * @param string $shippAddress
     */
    public function iChangeOrderWithIdOfTheShippingAddressTo(int $orderId, string $shippAddress)
    {
        AnnotationBasedMessagingContext::getCommandBus()->send(ChangeShippingAddressCommand::create($orderId, 0, $shippAddress));
    }

    /**
     * @Then shipping address should be :shippingAddress for order with id :orderId
     *
     * @param string $shippingAddress
     * @param int $orderId
     */
    public function shippingAddressShouldBeForOrderWithId(string $shippingAddress, int $orderId)
    {
        $execute = AnnotationBasedMessagingContext::getQueryBus()->send(GetShippingAddressQuery::create($orderId));
        $this->assertEquals($shippingAddress, $execute);
    }

    /**
     * @Then there should be :productsAmount products for order with id :orderId retrieved from :channelName
     *
     * @param int $productsAmount
     * @param int $orderId
     * @param string $channelName
     *
     */
    public function thereShouldBeProductsForOrderWithIdRetrievedFrom(int $productsAmount, int $orderId, string $channelName)
    {
        $executeWithContentType = AnnotationBasedMessagingContext::getQueryBus()->convertAndSend($channelName, MediaType::APPLICATION_X_PHP_SERIALIZED_OBJECT, serialize(GetOrderAmountQuery::createWith($orderId)));
        $this->assertEquals(
            $productsAmount,
            $executeWithContentType
        );
    }


    /**
     * @Then there should notification :numberOfNotifications awaiting notification
     *
     * @param int $numberOfNotifications
     */
    public function thereShouldNotificationAwaitingNotification(int $numberOfNotifications)
    {
        $this->assertCount($numberOfNotifications, AnnotationBasedMessagingContext::getQueryBus()->convertAndSend(
            "getOrderNotifications",
            MediaType::APPLICATION_X_PHP_OBJECT,
            []
        ));
    }
}

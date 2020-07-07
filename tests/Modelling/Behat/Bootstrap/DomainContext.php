<?php

namespace Test\Ecotone\Modelling\Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Ecotone\Messaging\Conversion\MediaType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Behat\Bootstrap\AnnotationBasedMessagingContext;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\EventWasLogged;

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
        $executeWithContentType = AnnotationBasedMessagingContext::getQueryBus()->convertAndSend($channelName, MediaType::APPLICATION_X_PHP_SERIALIZED, serialize(GetOrderAmountQuery::createWith($orderId)));
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
            MediaType::APPLICATION_X_PHP,
            []
        ));
    }

    /**
     * @When I register shop with margin :margin
     */
    public function iRegisterShopWithMargin(int $margin)
    {
        AnnotationBasedMessagingContext::getCommandBus()->convertAndSend(
            "shop.register",
            MediaType::APPLICATION_X_PHP_ARRAY,
            ["shopId" => 1, "margin" => $margin]
        );
    }

    /**
     * @Then for :productType product there should be price of :expectedPrice
     */
    public function forProductThereShouldBePriceOf(string $productType, int $expectedPrice)
    {
        $this->assertEquals(
            $expectedPrice,
            AnnotationBasedMessagingContext::getQueryBus()->convertAndSend(
                "shop.calculatePrice",
                MediaType::APPLICATION_X_PHP_ARRAY,
                ["shopId" => 1, "productType" => $productType]
            )
        );
    }

    /**
     * @When current time is :currentTime
     */
    public function currentTimeIs(string $currentTime)
    {
        AnnotationBasedMessagingContext::getCommandBus()->convertAndSend(
            "changeCurrentTime",
            MediaType::APPLICATION_X_PHP,
            $currentTime
        );
    }

    /**
     * @When I send log with information :logData
     */
    public function iSendLogWithInformation(string $logData)
    {
        AnnotationBasedMessagingContext::getCommandBus()->convertAndSend(
            "log",
            MediaType::APPLICATION_X_PHP_ARRAY,
            [
                "loggerId" => 1,
                "data" => $logData
            ]
        );
    }

    /**
     * @When current user is :currentUser
     */
    public function currentUserIs(string $currentUser)
    {
        AnnotationBasedMessagingContext::getCommandBus()->convertAndSend(
            "changeExecutorId",
            MediaType::APPLICATION_X_PHP,
            $currentUser
        );
    }

    /**
     * @Then there should be log for :expectedLogData at time :expectedTime and user :userId
     */
    public function thereShouldBeLogForAtTimeAndUser(string $expectedLogData, string $expectedTime, string $userId)
    {
        Assert::assertEquals(
            [
                "event" => new EventWasLogged(["data" => $expectedLogData, "executorId" => $userId, "loggerId" => 1]),
                "happenedAt" => $expectedTime
            ],
            AnnotationBasedMessagingContext::getQueryBus()->convertAndSend(
                "getLastLog",
                MediaType::APPLICATION_X_PHP_ARRAY,
                []
            )
        );
    }

    /**
     * @When I send log with information :logInfo I should be disallowed
     */
    public function iSendLogWithInformationIShouldBeDisallowed(string $logInfo)
    {
        $exception = false;
        try {
            $this->iSendLogWithInformation($logInfo);
        }catch (\InvalidArgumentException $exception) {
            $exception = true;
        }

        Assert::assertTrue($exception, "User was allowed to store logs on someones else stream");
    }

    /**
     * @When I notify about order with information :logData
     */
    public function iNotifyAboutOrderWithInformation(string $logData)
    {
        AnnotationBasedMessagingContext::getEventBus()->convertAndSend(
            "order.was_created",
            MediaType::APPLICATION_X_PHP_ARRAY,
            [
                "loggerId" => 1,
                "data" => $logData
            ]
        );
    }

    /**
     * @When I notify about order with information :logData I should be disallowed
     */
    public function iNotifyAboutOrderWithInformationIShouldBeDisallowed(string $logData)
    {
        $exception = false;
        try {
            $this->iNotifyAboutOrderWithInformation($logData);
        }catch (\InvalidArgumentException $exception) {
            $exception = true;
        }

        Assert::assertTrue($exception, "User was allowed to store logs on someones else stream");
    }
}

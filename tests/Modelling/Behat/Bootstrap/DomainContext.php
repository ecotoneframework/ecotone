<?php

namespace Test\Ecotone\Modelling\Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Modelling\DistributionEntrypoint;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Behat\Bootstrap\AnnotationBasedMessagingContext;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\Ecotone\Modelling\Fixture\DistributedCommandHandler\ShoppingCenter;
use Test\Ecotone\Modelling\Fixture\DistributedEventHandler\ShoppingRecord;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\FinishJob;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\StartJob;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\EventWasLogged;
use Test\Ecotone\Modelling\Fixture\MetadataPropagating\PlaceOrder;

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
        $executeWithContentType = AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting($channelName, serialize(GetOrderAmountQuery::createWith($orderId)), MediaType::APPLICATION_X_PHP_SERIALIZED);
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
        $this->assertCount($numberOfNotifications, AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting(
            "getOrderNotifications",
            []
        ));
    }

    /**
     * @When I register shop with margin :margin
     */
    public function iRegisterShopWithMargin(int $margin)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "shop.register",
            ["shopId" => 1, "margin" => $margin],
            MediaType::APPLICATION_X_PHP_ARRAY
        );
    }

    /**
     * @Then for :productType product there should be price of :expectedPrice
     */
    public function forProductThereShouldBePriceOf(string $productType, int $expectedPrice)
    {
        $this->assertEquals(
            $expectedPrice,
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting(
                "shop.calculatePrice",
                ["shopId" => 1, "productType" => $productType]
            )
        );
    }

    /**
     * @When current time is :currentTime
     */
    public function currentTimeIs(string $currentTime)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "changeCurrentTime",
            $currentTime
        );
    }

    /**
     * @When I send log with information :logData
     */
    public function iSendLogWithInformation(string $logData)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "log",
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
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "changeExecutorId",
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
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting(
                "getLastLog",
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
        AnnotationBasedMessagingContext::getEventBus()->publishWithRouting(
            "order.was_created",
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

    /**
     * @When I place order with metadata :headerName :value
     */
    public function iPlaceOrderWithMetadata(string $headerName, $value)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "placeOrder",
            [],
            MediaType::APPLICATION_X_PHP_ARRAY,
            [$headerName => $value]
        );
    }

    /**
     * @When I place order with no additional metadata
     */
    public function iPlaceOrderWithNoAdditionalMetadata()
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "placeOrder",
            [],
            MediaType::APPLICATION_X_PHP_ARRAY
        );
    }

    /**
     * @Then there should be notification with metadata :headerName :value
     */
    public function thereShouldBeNotificationWithMetadata(string $headerName, $value)
    {
        $this->assertEquals(
            $value,
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting("getNotificationHeaders", [])[$headerName]
        );
    }

    /**
     * @Then there should be notification without additional metadata
     */
    public function thereShouldBeNotificationWithoutAdditionalMetadata()
    {
        $this->assertArrayNotHasKey(
            "token",
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting("getNotificationHeaders", [])
        );
    }

    /**
     * @When I override header :headerName with :value
     */
    public function iOverrideHeaderWith(string $headerName, $value)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "setCustomNotificationHeaders",
            [],
            MediaType::APPLICATION_X_PHP_ARRAY,
            [$headerName => $value]
        );
    }

    /**
     * @When next command fails with :headerName :headerValue
     */
    public function nextCommandFailsWith(string $headerName, $headerValue)
    {
        try {
            AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
                "failAction",
                [],
                MediaType::APPLICATION_X_PHP_ARRAY,
                [$headerName => $headerValue]
            );
        }catch (\Exception $exception) {}
    }

    /**
     * @Given current user id :userId
     */
    public function currentUserId(string $userId)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "addCurrentUserId",
            $userId
        );
    }

    /**
     * @Then basket should contains :item
     */
    public function basketShouldContains(string $item)
    {
        $this->assertContains(
            $item,
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting(
                "basket.get",
                [
                    "item" => $item
                ]
            )
        );
    }

    /**
     * @When I add to basket :arg1
     */
    public function iAddToBasket(string $item)
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "basket.add",
            [
                "item" => $item
            ],
            MediaType::APPLICATION_X_PHP_ARRAY
        );
    }

    /**
     * @When I doing distributed order :order
     */
    public function iDoingDistributedOrder(string $order)
    {
        AnnotationBasedMessagingContext::getGateway(DistributionEntrypoint::class)->distribute(
            $order, [], "command", ShoppingCenter::SHOPPING_BUY, MediaType::TEXT_PLAIN
        );
    }

    /**
     * @Then there should be :amount good ordered
     */
    public function thereShouldBeGoodOrdered(int $amount)
    {
        $this->assertEquals($amount, AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting(
            ShoppingCenter::COUNT_BOUGHT_GOODS, []
        ));
    }

    /**
     * @When :order was order
     */
    public function wasOrder(string $order)
    {
        AnnotationBasedMessagingContext::getGateway(DistributionEntrypoint::class)->distribute(
            $order, [], "event", ShoppingRecord::ORDER_WAS_MADE, MediaType::TEXT_PLAIN
        );
    }

    /**
     * @Then basket metadata should contains metadata:
     */
    public function basketMetadataShouldContainsMetadata(TableNode $table)
    {
        $result = AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting("basket.get", []);
        foreach ($table->getHash() as $node) {
            $this->assertEquals($node['value'], $result[$node['name']]);
        }
    }

    /**
     * @When I remove last item from basket
     */
    public function iRemoveLastItemFromBasket()
    {
        AnnotationBasedMessagingContext::getCommandBus()->sendWithRouting(
            "basket.removeLast",
            [],
            MediaType::APPLICATION_X_PHP_ARRAY
        );
    }

    /**
     * @When I register job with id :id
     */
    public function iRegisterJobWithId(int $id)
    {
        AnnotationBasedMessagingContext::getCommandBus()->send(new StartJob($id));
    }

    /**
     * @Then job with id of :id should be :status
     */
    public function jobWithIdOfShouldBe(int $id, string $status)
    {
        $this->assertEquals(
            $status == "in progress",
            AnnotationBasedMessagingContext::getQueryBus()->sendWithRouting("job.isInProgress", [
                "id" => $id
            ])
        );
    }

    /**
     * @When I finish job with id :id
     */
    public function iFinishJobWithId(int $id)
    {
        AnnotationBasedMessagingContext::getCommandBus()->send(new FinishJob($id));
    }
}

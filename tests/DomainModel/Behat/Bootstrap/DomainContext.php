<?php

namespace Test\SimplyCodedSoftware\DomainModel\Behat\Bootstrap;

require_once __DIR__ . "/../../../TestBootstrap.php";

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\DomainModel\CommandBus;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessageRouterModule;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\DomainModel\MessageGateway;
use SimplyCodedSoftware\DomainModel\QueryBus;
use SimplyCodedSoftware\DomainModel\QueryGateway;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Conversion\SerializedToObject\DeserializingConverterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryOrderRepositoryFactory;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\Notification;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\Order;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Test\SimplyCodedSoftware\DomainModel\Fixture\LazyEventBus;

/**
 * Defines application features from the specific context.
 */
class DomainContext extends TestCase implements Context
{
    /**
     * @var QueryBus
     */
    private $queryBus;
    /**
     * @var CommandBus
     */
    private $commandBus;
    /**
     * @var EventBus
     */
    private $eventBus;
    /**
     * @var OrderNotificator
     */
    private $orderNotificator;

    /**
     * @Given I have order with id :orderId for :productAmount products registered to shipping address :shippingAddress
     *
     * @param int    $orderId
     * @param int    $productAmount
     * @param string $shippAddress
     */
    public function iHaveOrderWithIdForProductsRegisteredToShippingAddress(int $orderId, int $productAmount, string $shippAddress)
    {
        $this->commandBus->send(CreateOrderCommand::create($orderId, $productAmount, $shippAddress));
    }

    /**
     * @When I change order with id of :orderId the shipping address to :shippingAddress
     *
     * @param int    $orderId
     * @param string $shippAddress
     */
    public function iChangeOrderWithIdOfTheShippingAddressTo(int $orderId, string $shippAddress)
    {
        $this->commandBus->send(ChangeShippingAddressCommand::create($orderId, 0, $shippAddress));
    }

    /**
     * @Then shipping address should be :shippingAddress for order with id :orderId
     *
     * @param string $shippingAddress
     * @param int    $orderId
     */
    public function shippingAddressShouldBeForOrderWithId(string $shippingAddress, int $orderId)
    {
        $execute = $this->queryBus->send(GetShippingAddressQuery::create($orderId));
        $this->assertEquals($shippingAddress, $execute);
    }



    /**
     * @BeforeScenario
     */
    public function setUpMessaging()
    {
        $this->prepareConfiguration([Order::class, OrderNotificator::class]);
    }

    /**
     * @Then there should be :productsAmount products for order with id :orderId retrieved from :channelName
     *
     * @param int    $productsAmount
     * @param int    $orderId
     * @param string $channelName
     *
     */
    public function thereShouldBeProductsForOrderWithIdRetrievedFrom(int $productsAmount, int $orderId, string $channelName)
    {
        $executeWithContentType = $this->queryBus->convertAndSend($channelName, MediaType::APPLICATION_X_PHP_SERIALIZED_OBJECT,serialize(GetOrderAmountQuery::createWith($orderId)));
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
        $this->assertCount($numberOfNotifications, $this->orderNotificator->getNotifications());
    }

    /**
     * @param array $classesWithAnnotationToRegister
     *
     * @return void
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function prepareConfiguration(array $classesWithAnnotationToRegister): void
    {
        $classesWithAnnotationToRegister = array_merge($classesWithAnnotationToRegister, [CommandBus::class, QueryBus::class, EventBus::class]);
        $aggregateMessagingModule        = AggregateMessagingModule::create(InMemoryAnnotationRegistrationService::createFrom($classesWithAnnotationToRegister));
        $annotationRegistrationService   = InMemoryAnnotationRegistrationService::createFrom($classesWithAnnotationToRegister);
        $gatewayModule                   = GatewayModule::create($annotationRegistrationService);
        $aggregateRouterModule           = AggregateMessageRouterModule::create($annotationRegistrationService);
        $this->orderNotificator          = new OrderNotificator();

        $lazyEventBus              = new LazyEventBus();
        $referenceSearchService    = InMemoryReferenceSearchService::createWith(
            [
                OrderNotificator::class => $this->orderNotificator,
                EventBus::class => $lazyEventBus
            ]
        );
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithCachedReferenceObjects(
            InMemoryModuleMessaging::createWith(
                [$aggregateMessagingModule, $gatewayModule, $aggregateRouterModule],
                [InMemoryOrderRepositoryFactory::createEmpty()]
            ),
            InMemoryReferenceTypeFromNameResolver::createFromAssociativeArray([
                Order::class => Order::class,
                OrderNotificator::class => OrderNotificator::class
            ])
        )
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConverter(new DeserializingConverterBuilder())
            ->buildMessagingSystemFromConfiguration($referenceSearchService);

        $this->commandBus = $configuredMessagingSystem->getGatewayByName(CommandBus::class);
        $this->queryBus   = $configuredMessagingSystem->getGatewayByName(QueryBus::class);
        $this->eventBus   = $configuredMessagingSystem->getGatewayByName(EventBus::class);
        $lazyEventBus->setEventBus($this->eventBus);
    }
}

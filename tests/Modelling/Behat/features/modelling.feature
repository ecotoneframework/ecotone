Feature: activating as aggregate order entity

  Scenario: I order product and change shipping address
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
    And I have order with id 1 for 20 products registered to shipping address "London 12th street"
    Then there should notification 1 awaiting notification
    And shipping address should be "London 12th street" for order with id 1
    When I change order with id of 1 the shipping address to "London 13th street"
    Then shipping address should be "London 13th street" for order with id 1
    And there should notification 2 awaiting notification
    And there should be 20 products for order with id 1 retrieved from "get_order_amount_channel"

  Scenario: I reorder product, then the amount should be increased
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate"
    When I have order with id 1 for 20 products registered to shipping address "London 12th street"
    And I have order with id 1 for 30 products registered to shipping address "London 52th street"
    Then there should be 50 products for order with id 1 retrieved from "get_order_amount_channel"

  Scenario: I rent appointment and expect to
  Calculations are combined with interceptors.
  This will focus on asynchronous scenario using inbound channel adapter
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Renter"
    When I rent appointment with id 123 and duration 100
    Then calendar should contain event with appointment id 123

  Scenario: I order product and I want to see it on the list of orders products using service
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\Order"
    When I order product "milk"
    Then there should be nothing on the order list
    When I active receiver "orders"
    Then on the order list I should see "milk"
    And notification list should be empty
    When I active receiver "orders"
    Then on notification list I should see "milk"

  Scenario: I order product and I want to see it on the list of orders products using aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\OrderAggregate"
    When I order product "milk"
    Then there should be no "milk" order
    When I active receiver "orders"
    Then there should be "milk" order
    And no notification for "milk"
    When I active receiver "orders"
    Then there should be notification about "milk" 1 time
    Then logs count be 0
    When I active receiver "orders"
    Then logs count be 1

  Scenario: Calculating price in specific shop. Make use of before, after interceptors and output channel for aggregate query handlers
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate"
    When I register shop with margin 20
#    (milk price (100) + shop margin (20) + franchise margin (10)) * vat (2.0)
    Then for "milk" product there should be price of 260

  Scenario: Storing logs. Make use of before, after interceptors and output channel for aggregate command handlers
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate"
    When current time is "2020-02-02 12:00:00"
    And current user is "Johny"
    And I send log with information "User logged in"
    Then there should be log for "User logged in" at time "2020-02-02 12:00:00" and user "Johny"
    When current time is "2020-02-02 12:10:00"
    And I send log with information "Another User logged in"
    Then there should be log for "Another User logged in" at time "2020-02-02 12:10:00" and user "Johny"

  Scenario: Storing logs. Make use of around interceptor for command aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate"
    And current time is "2020-02-02 12:00:00"
    And current user is "Johny"
    And I send log with information "User logged in"
    Then current user is "Franco"
    When I send log with information "Another User logged in" I should be disallowed

  Scenario: Storing logs. Make use of before, after interceptors and output channel for aggregate event handlers
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate"
    When current time is "2020-02-02 12:00:00"
    And current user is "Johny"
    And I notify about order with information "Milk was bought"
    Then there should be log for "Milk was bought" at time "2020-02-02 12:00:00" and user "Johny"
    When current time is "2020-02-02 12:10:00"
    And I notify about order with information "Ham was bought"
    Then there should be log for "Ham was bought" at time "2020-02-02 12:10:00" and user "Johny"

  Scenario: Storing logs. Make use of around interceptor for event aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate"
    And current time is "2020-02-02 12:00:00"
    And current user is "Johny"
    And I notify about order with information "Milk was bought"
    Then current user is "Franco"
    When I notify about order with information "Ham was bought" I should be disallowed

  Scenario: Placing order and notifying. Verify correctness of propagated headers
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\MetadataPropagating"
    When I place order with metadata "token" 123
    Then there should be notification with metadata "token" 123

  Scenario: Placing order and notifying. Verify correctness overriding propagated headers
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\MetadataPropagating"
    And I override header "token" with 1234
    When I place order with metadata "token" 123
    Then there should be notification with metadata "token" 1234

  Scenario: Placing order and notifying. Verify correctness overriding propagated headers when exception happened
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\MetadataPropagating"
    When I place order with metadata "token" 123
    And next command fails with "token" 1111
    When I place order with no additional metadata
    Then there should be notification without additional metadata

  Scenario: Placing order and notifying. Verify correctness overriding propagated headers when there is more endpoints involved
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints"
    When I place order with metadata "token" 123
    Then there should be notification with metadata "token" 123
    When I active receiver "notifications"
    Then there should be notification with metadata "token" 123

  Scenario: Handle presend interceptor for aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptingAggregate"
    And current user id 123
    When I add to basket "milk"
    Then basket should contains "milk"
    When I add to basket "cheese"
    Then basket should contains "cheese"

  Scenario: Handle presend interceptor for aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes"
    And current user id 123
    When I add to basket "milk"
    Then basket metadata should contains metadata:
      | name           | value      |
      | isRegistration | true       |
      | handlerInfo    | basket.add |
    When I add to basket "cheese"
    Then basket metadata should contains metadata:
      | name           | value      |
      | isRegistration | false      |
      | handlerInfo    | basket.add |

  Scenario: command handler distribution
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\DistributedCommandHandler"
    When I doing distributed order "pizza"
    Then there should be 1 good ordered

  Scenario: event handler distribution
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\DistributedEventHandler"
    When "pizza" was order
    Then there should be 1 good ordered

  Scenario: Handle multiple handler at the same method
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\MultipleHandlersAtSameMethod"
    When I add to basket "milk"
    Then basket should contains "milk"
    When I add to basket "cheese"
    And I remove last item from basket
    Then basket should contains "milk"

  Scenario: Handle aggregate with internal event recorder
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder"
    When I register job with id 1
    Then job with id of 1 should be "in progress"
    When I finish job with id 1
    Then job with id of 1 should be "finished"

  Scenario: Handle publish named events from aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\NamedEvent"
    When I register guest book with id 1
    And I add guest "Frank" to book 1
    Then view guest list of book 1 then
      | Frank |

  Scenario: Handle two sagas handling same events
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\TwoSagas"
    When order with id 5 was placed
    Then bookkeeping status for order 5 should be "awaitingPayment"
    And shipment status for order 5 should be "awaitingPayment"
    When order with id 5 was paid
    Then bookkeeping status for order 5 should be "paid"
    And shipment status for order 5 should be "shipped"

  Scenario: Handle two asynchronous sagas handling same events
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas"
    When order with id 5 was placed
    And I active receiver "asynchronous_channel"
    And I active receiver "asynchronous_channel"
    Then bookkeeping status for order 5 should be "awaitingPayment"
    And shipment status for order 5 should be "awaitingPayment"
    When order with id 5 was paid
    And I active receiver "asynchronous_channel"
    And I active receiver "asynchronous_channel"
    Then bookkeeping status for order 5 should be "paid"
    And shipment status for order 5 should be "shipped"

  Scenario: Handle simplified aggregate
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\SimplifiedAggregate"
    When I register create aggregate
    And I enable aggregate
    Then it should be enabled

  Scenario: Handle repository shortcut
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\RepositoryShortcut"
    And twit with id "123" does not exists
    When I create twit with id "123" and content "bla"
    Then twit with id "123" it should contains "bla"
    When it change twit with id "123" to content "ha!"
    Then twit with id "123" it should contains "ha!"

  Scenario: Handle event sourcing repository shortcut
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\EventSourcingRepositoryShortcut"
    And twit with id "123" does not exists
    When I create twit with id "123" and content "bla"
    Then twit with id "123" it should contains "bla"
    When it change twit with id "123" to content "ha!"
    Then twit with id "123" it should contains "ha!"

  Scenario: Handle aggregate with generated id after it's saved
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation"
    When I create user then id should be assigned

  Scenario: Handle aggregate with aggregate identifier as public method
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod"
    When I create user with id 1 and name "johny"
    Then there should be user with id 1 and name "johny"

  Scenario: Handle aggregate with generated id after it's saved and identifier retrieved from public method
    Given I active messaging for namespace "Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod"
    When I create user then id should be assigned from public method
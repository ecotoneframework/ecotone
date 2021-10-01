Feature: Service activator

  Scenario: Connection between service activator and gateway. Where gateway use only default payload transformer
    Given I configure messaging system
    And I register "bookingRequest" as "Direct Channel"
    And I register "bookingConfirmation" as "Direct Channel"
    And I register "bookingConfirmationResponse" as "Pollable Channel"
    And I activate service with name "bookHandler" for "Test\Ecotone\Messaging\Fixture\Behat\Booking\Booking" with method "book" to listen on "bookingRequest" channel
    And I activate service with name "isBookedHandler" for "Test\Ecotone\Messaging\Fixture\Behat\Booking\Booking" with method "isBooked" to listen on "bookingConfirmation" channel and output channel "bookingConfirmationResponse"
    And I activate gateway with name "bookingGateway" for "Test\Ecotone\Messaging\Fixture\Behat\Booking\BookingService" and "bookFlat" with request channel "bookingRequest"
    And I activate gateway with name "checkGateway" for "Test\Ecotone\Messaging\Fixture\Behat\Booking\BookingService" and "checkIfIsBooked" with request channel "bookingConfirmation" and reply channel "bookingConfirmationResponse"
    And I run messaging system
    When I book flat with id 3 using gateway "bookingGateway"
    Then flat with id 3 should be reserved when checked by "checkGateway"

  Scenario: Connection between gateway and service activator with transformer between
    Given I configure messaging system
    And I register "reserveRequest" as "Direct Channel"
    And I register "reserveRequestTransformer" as "Direct Channel"
    And I register "reservationResponse" as "Pollable Channel"
    And I activate service with name "bookshopReservation" for "Test\Ecotone\Messaging\Fixture\Behat\Shopping\Bookshop" with method "reserve" to listen on "reserveRequestTransformer" channel and output channel "reservationResponse"
    And I activate gateway with name "reserveGateway" for "Test\Ecotone\Messaging\Fixture\Behat\Shopping\ShoppingService" and "reserve" with request channel "reserveRequest" and reply channel "reservationResponse"
    And I activate transformer with name "reservationTransformer" for "Test\Ecotone\Messaging\Fixture\Behat\Shopping\ToReservationRequestTransformer" and "transform" with request channel "reserveRequest" and output channel "reserveRequestTransformer"
    And I run messaging system
    When I reserve book named "Harry Potter" using gateway "reserveGateway"

  Scenario: Application consist of order service. It receives and order and return confirmation.
      Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
      At request channel message will be transformed to contain isAsync header
      At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
      This will focus on synchronous scenario.
    Given I configure messaging system
    And I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     0 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "syncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I send order request with id 3 product name "correct" using gateway "orderingService"
    Then I should receive confirmation

  Scenario: Application consist of order service. It receives and order and return confirmation.
  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
  At request channel message will be transformed to contain isAsync header
  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
  This will focus on asynchronous scenario.
    Given I configure messaging system
    And I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     1 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "asyncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I send order request with id 3 product name "correct" using gateway "orderingService"
    And "orderProcessor" handles message
    Then I should receive confirmation

  Scenario: Application consist of order service. It receives and order and return confirmation.
  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
  At request channel message will be transformed to contain isAsync header
  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
  This will focus on synchronous   exception   scenario.
    Given I configure messaging system
    And I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     0 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "syncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I expect exception when sending order request with id 3 product name "INCORRECT" using gateway "orderingService"

  Scenario: Application consist of order service. It receives and order and return confirmation.
  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
  At request channel message will be transformed to contain isAsync header
  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
  This will focus on  exception  asynchronous scenario.
    Given I configure messaging system
    And I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     1 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "asyncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I send order request with id 3 product name "INCORRECT" using gateway "orderingService"
    And "orderProcessor" handles message with exception

  Scenario: Application consist of calculator. It receives number and perform few calculations.
    Calculations are combined with interceptors.
    This will focus on synchronous scenario using gateway
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\Calculating"
    When I calculate for 3 using gateway then result should be 18

  Scenario: Application consist of calculator. It receives number and perform few calculations.
    Calculations are combined with interceptors.
    This will focus on asynchronous scenario using inbound channel adapter
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\Calculating"
    When I calculate using inbound channel adapter
    Then result should be 15 in "resultChannel" channel

  Scenario: Application exception handling
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling"
    When I order "coffee"
    And I call pollable endpoint "orderService"
    Then there should no error order
    And I call pollable endpoint "orderService"
    Then there should no error order
    And I call pollable endpoint "orderService"
    Then there should be error order "coffee"

  Scenario: Application handling gateways inside gateways
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway"
    When I call "Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\CalculateGatewayExample" with 2 I should receive 76

  Scenario: Application handling interceptors for gateway
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway"
    When I call "Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\CalculateGatewayExample" with 2 I should receive 10

  Scenario: Application handling gateways inside gateways using only messages
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages"
    When I call with 2 I should receive 76 with message

  Scenario: Handle presend interceptor
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\Presend"
    When I store 100 coins
    Then result should be 200 in "shop" channel

  Scenario: Handle intercepted scheduled endpoint in recursion
    Given I active messaging for namespace "Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled"
    When I call pollable endpoint "scheduled.handler"
    Then result from scheduled endpoint should be 160
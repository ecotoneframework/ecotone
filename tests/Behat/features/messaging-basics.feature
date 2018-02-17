Feature: Service activator

  Scenario: Connection between service activator and gateway. Where gateway use only default payload transformer
    Given I register "bookingRequest" as "Direct Channel"
    And I register "bookingConfirmation" as "Direct Channel"
    And I register "bookingConfirmationResponse" as "Pollable Channel"
    And I activate service with name "bookHandler" for "Fixture\Behat\Booking\Booking" with method "book" to listen on "bookingRequest" channel
    And I activate service with name "isBookedHandler" for "Fixture\Behat\Booking\Booking" with method "isBooked" to listen on "bookingConfirmation" channel and output channel "bookingConfirmationResponse"
    And I activate gateway with name "bookingGateway" for "Fixture\Behat\Booking\BookingService" and "bookFlat" with request channel "bookingRequest"
    And I activate gateway with name "checkGateway" for "Fixture\Behat\Booking\BookingService" and "checkIfIsBooked" with request channel "bookingConfirmation" and reply channel "bookingConfirmationResponse"
    And I run messaging system
    When I book flat with id 3 using gateway "bookingGateway"
    Then flat with id 3 should be reserved when checked by "checkGateway"


  Scenario: Connection between gateway and service activator with transformer between
    Given I register "reserveRequest" as "Direct Channel"
    And I register "reserveRequestTransformer" as "Direct Channel"
    And I register "reservationResponse" as "Pollable Channel"
    And I activate service with name "bookshopReservation" for "Fixture\Behat\Shopping\Bookshop" with method "reserve" to listen on "reserveRequestTransformer" channel and output channel "reservationResponse"
    And I activate gateway with name "reserveGateway" for "Fixture\Behat\Shopping\ShoppingService" and "reserve" with request channel "reserveRequest" and reply channel "reservationResponse"
    And I activate transformer with name "reservationTransformer" for "Fixture\Behat\Shopping\ToReservationRequestTransformer" and "transform" with request channel "reserveRequest" and output channel "reserveRequestTransformer"
    And I run messaging system
    When I reserve book named "Harry Potter" using gateway "reserveGateway"


  Scenario: Application consist of order service. It receives and order and return confirmation.
      Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
      At request channel message will be transformed to contain isAsync header
      At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
      This will focus on synchronous scenario.
    Given I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     0 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "syncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I send order request with id 3 product name "correct" using gateway "orderingService"
    Then I should receive confirmation

  Scenario: Application consist of order service. It receives and order and return confirmation.
  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
  At request channel message will be transformed to contain isAsync header
  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
  This will focus on asynchronous scenario.
    Given I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     1 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "asyncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I send order request with id 3 product name "correct" using gateway "orderingService"
    And "orderProcessor" handles message
    Then I should receive confirmation

  Scenario: Application consist of order service. It receives and order and return confirmation.
  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
  At request channel message will be transformed to contain isAsync header
  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
  This will focus on synchronous   exception   scenario.
    Given I register "requestChannel" as "Direct Channel"
    And I register "routeChannel" as "Direct Channel"
    And I register "syncChannel" as "Direct Channel"
    And I register "asyncChannel" as "Pollable Channel"
    And I register "responseChannel" as "Pollable Channel"
    And I activate gateway with name "orderingService" for "Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
      | key     | value |
      | isAsync |     0 |
    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
      | value | target_channel |
      |     1 | asyncChannel   |
      |     0 | syncChannel    |
    And I activate service with name "orderProcessor" for "Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "syncChannel" channel and output channel "responseChannel"
    And I run messaging system
    When I expect exception when sending order request with id 3 product name "INCORRECT" using gateway "orderingService"

  ## get back to this one, when inbound channel adapter will be available
#  Scenario: Application consist of order service. It receives and order and return confirmation.
#  Gateway is entry point to the messaging system, it will receive the order and send it to request channel.
#  At request channel message will be transformed to contain isAsync header
#  At route channel router will route message to proper "syncChannel" or "asyncChannel", based on "isAsync" header
#  This will focus on  exception  asynchronous scenario.
#    Given I register "requestChannel" as "Direct Channel"
#    And I register "routeChannel" as "Direct Channel"
#    And I register "syncChannel" as "Direct Channel"
#    And I register "asyncChannel" as "Pollable Channel"
#    And I register "responseChannel" as "Pollable Channel"
#    And I activate gateway with name "orderingService" for "Fixture\Behat\Ordering\OrderingService" and "processOrder" with request channel "requestChannel" and reply channel "responseChannel"
#    And I activate header enricher transformer with name "transformer" with request channel "requestChannel" and output channel "routeChannel" with headers:
#      | key     | value |
#      | isAsync |     1 |
#    And I activate header router with name "routing" and input Channel "routeChannel" for header "isAsync" with mapping:
#      | value | target_channel |
#      |     1 | asyncChannel   |
#      |     0 | syncChannel    |
#    And I activate service with name "orderProcessor" for "Fixture\Behat\Ordering\OrderProcessor" with method "processOrder" to listen on "asyncChannel" channel and output channel "responseChannel"
#    And I run messaging system
#    When I send order request with id 3 product name "INCORRECT" using gateway "orderingService"
#    And "orderProcessor" handles message
#    Then I expect exception during confirmation receiving


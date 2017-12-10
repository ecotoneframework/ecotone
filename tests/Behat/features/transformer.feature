Feature: transformer

  Scenario: Connection between gateway and service activator with transformer between
    Given I register "reserveRequest" as "Direct Channel"
    And I register "reserveRequestTransformer" as "Direct Channel"
    And I register "reservationResponse" as "Pollable Channel"
    And I activate service with name "bookshopReservation" for "Fixture\Behat\Shopping\Bookshop" with method "reserve" to listen on "reserveRequestTransformer" channel and output channel "reservationResponse"
    And I activate gateway with name "reserveGateway" for "Fixture\Behat\Shopping\ShoppingService" and "reserve" with request channel "reserveRequest" and reply channel "reservationResponse"
    And I activate transformer with name "reservationTransformer" for "Fixture\Behat\Shopping\ToReservationRequestTransformer" and "transform" with request channel "reserveRequest" and output channel "reserveRequestTransformer"
    And I run messaging system
    When I reserve book named "Harry Potter" using gateway "reserveGateway"

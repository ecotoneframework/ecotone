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
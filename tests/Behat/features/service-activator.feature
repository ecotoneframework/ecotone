Feature: As a user of the system I want to manage flow

  Scenario: Registering flat.
      Testing connection between service activator and gateway. Where gateway use only default payload transformer
    Given I register "bookingRequest" as "Direct Channel"
    And I register "bookingConfirmation" as "Direct Channel"
    And I activate service "Fixture\Behat\Booking\Booking" with method "book" to listen on "bookingRequest" channel
    And I activate service "Fixture\Behat\Booking\Booking" with method "isBooked" to listen on "bookingConfirmation" channel
    And I set gateway for "Fixture\Behat\Booking\BookingService" and "bookFlat"
    When I book flat with id 3
    Then flat with id 3 should be reserved
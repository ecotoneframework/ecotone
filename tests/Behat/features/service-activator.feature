Feature: As a user of the system I want to manage flow

  Scenario: As system operator I want to register flat bookings
    Given I activate service "Fixture\Behat\Booking\Booking" with method "book" to listen on "bookingRequest" channel
    And I activate service "Fixture\Behat\Booking\Booking" with method "isBooked" to listen on "bookingConfirmation" channel
    And I set gateway for "Fixture\Behat\Booking\BookingService" and "bookFlat" and method parameters:
      | name | definition |
    When message with payload "123" comes to "bookingConfirmationRequest" channel
    Then
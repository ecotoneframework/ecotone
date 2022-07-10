Feature: activating as aggregate order entity

  Scenario: I order with transaction a product with failure, so the order should never be committed to database
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\Transaction"
#    this is step for non ddl transactional databases
    And table is prepared
    And there should 0 registered orders
    When I transactionally order "milk"
    Then there should 0 registered orders

  Scenario: Handles rollback transaction that was caused by non DDL statement ending with failure later
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\Transaction"
    When it fails prepare table
    And there should 0 registered orders
    When I transactionally order "milk"
    Then there should 0 registered orders

  Scenario: When it fails at second time to add the order, it will rollback the message
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction"
    When I transactionally order "milk"
    Then there should 0 registered orders
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 1 registered orders
    When I transactionally order "milk"
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 1 registered orders

  Scenario: Handles commit transaction that were ended by implicit commit. Test for non DDL transactional databases
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction"
    When I transactionally order "milk" with table creation
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 1 registered orders

  Scenario: Handles rollback transaction that was caused by non DDL statement with success later
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction"
    When it fails prepare table
    When I transactionally order "milk"
    And I call pollable endpoint "orders"
    And I call pollable endpoint "processOrders"
    Then there should 1 registered orders

  Scenario: Application exception handling
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\DeadLetter"
    When I order "coffee"
    And I call pollable endpoint "orderService"
    Then there should be 0 orders
    And I call pollable endpoint "orderService"
    Then there should be 0 orders
    And there should 1 error message in dead letter
    When all error messages are replied
    Then there should 0 error message in dead letter
    When I call pollable endpoint "orderService"
    And there should be 1 orders

  Scenario: Provide support for ORM
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\ORM"
    When I register person with id 100 and name "Johny"
    Then there person with id 100 should be named "Johny"

  Scenario: Provide support for Dbal Document Store using different collections
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\DocumentStore"
    When I place order nr 100 for "milk" in "milky_shop"
    Then there should be order nr 100 in "milky_shop" with "milk"
    And there should 1 order placed in "milky_shop"
    When I place order nr 1 for "ham" in "meat_shop"
    Then there should be order nr 1 in "meat_shop" with "ham"
    And there should 1 order placed in "meat_shop"

  Scenario: Provide support for Dbal Document Store
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\DocumentStore"
    And I place order nr 100 for "milk" in "milky_shop"
    When I update order nr 100 in "milky_shop" to "water"
    Then there should be order nr 100 in "milky_shop" with "water"
    When I upsert order nr 101 for "coffee" in "milky_shop"
    Then there should be order nr 101 in "milky_shop" with "coffee"
    And there should 2 order placed in "milky_shop"
    When I delete order nr 100 in "milky_shop"
    And there should 1 order placed in "milky_shop"

  Scenario: Provide support for In Memory Document Store using different collections
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\InMemoryDocumentStore"
    When I place order nr 100 for "milk" in "milky_shop"
    Then there should be order nr 100 in "milky_shop" with "milk"
    And there should 1 order placed in "milky_shop"
    When I place order nr 1 for "ham" in "meat_shop"
    Then there should be order nr 1 in "meat_shop" with "ham"
    And there should 1 order placed in "meat_shop"

  Scenario: Provide support for In Memory Document Store
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\InMemoryDocumentStore"
    And I place order nr 100 for "milk" in "milky_shop"
    When I update order nr 100 in "milky_shop" to "water"
    Then there should be order nr 100 in "milky_shop" with "water"
    When I upsert order nr 101 for "coffee" in "milky_shop"
    Then there should be order nr 101 in "milky_shop" with "coffee"
    And there should 2 order placed in "milky_shop"
    When I delete order nr 100 in "milky_shop"
    And there should 1 order placed in "milky_shop"

  Scenario: Provide support for Document Store aggregate
    Given I active messaging for namespace "Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate"
    When I register person with id 100 and name "Johny" for document aggregate
    Then there person with id 100 should be named "Johny"  for document aggregate
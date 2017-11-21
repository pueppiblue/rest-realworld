Feature: Authentication
  In order to access protected resources
  As an API Client
  I need to be able to authenticate

  Background:
  # actually does create the user in the database
    Given the user "weaverryan" exists
    And the user "pueppiblue" exists
    And "weaverryan" has an authentication token "ABC123"
    And "pueppiblue" has an authentication token "XYZ789"

  Scenario: Create a programmmer without being authenticated
    When I request "POST /api/programmers"
    Then the response status code should be 401
    And the "detail" property should contain "Authentication Required"

  Scenario: Sending an invalid token results in a 401
    Given I set the "Authorization" header to be "token ABCFAKETOKEN"
    When I request "POST /api/programmers"
    Then the response status code should be 401
    And the "detail" property should contain "Invalid Credentials!"

  Scenario: Update|DELETE a programmer without being authenticated
    Given the following programmers exist:
      | nickname   | avatarNumber |
      | UnitTester | 3            |
    When I request "PUT /api/programmers/UnitTester"
    Then the response status code should be 401
    And the "detail" property should contain "Authentication Required"
    When I request "PATCH /api/programmers/UnitTester"
    Then the response status code should be 401
    And the "detail" property should contain "Authentication Required"
    Then the response status code should be 401
    And the "detail" property should contain "Authentication Required"

  Scenario: Update|DELETE a programmer i do not own
    Given the following programmers exist:
      | nickname   | avatarNumber | owner      |
      | UnitTester | 3            | weaverryan |
    And I set the "Authorization" header to be "token XYZ789"
    When I request "PUT /api/programmers/UnitTester"
    Then the response status code should be 403
    And the "detail" property should contain "not the owner of this programmer"
    When I request "PATCH /api/programmers/UnitTester"
    Then the response status code should be 403
    And the "detail" property should contain "not the owner of this programmer"
    Then the response status code should be 403
    And the "detail" property should contain "not the owner of this programmer"

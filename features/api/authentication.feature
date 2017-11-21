Feature: Authentication
  In order to access protected resources
  As an API Client
  I need to be able to authenticate

  Scenario: Create a programmmer wihtout being authenticated
    When I request "POST /api/programmers"
    Then the response status code should be 401
    And the "detail" property should contain "Authentication Required"

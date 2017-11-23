Feature: Token
  In order to access protected resources
  As an API Client
  I need to be able to create new tokens
  and use them to access protected information

  Background:
  # actually does create the user in the database
    Given there is a user "weaverryan" with password "test"


  Scenario: Create a token
    Given I authenticate with user "weaverryan" and password "test"
    And I have the payload:
    """
      {
        "notes": "This is a testing token!"
      }
    """
    When I request "POST /api/tokens"
    Then the response status code should be 201
    And the "Location" header should exist
    And the "token" property should be a string

  Scenario: Create a token with wrong credentials
    Given I authenticate with user "weaverryan" and password "wrong_password"
    And I have the payload:
    """
      {
        "notes": "This is a testing token!"
      }
    """
    When I request "POST /api/tokens"
    Then the response status code should be 401


  Scenario: Create a token without notes properties
    Given I authenticate with user "weaverryan" and password "test"
    And I have the payload:
    """
      {
      }
    """
    When I request "POST /api/tokens"
    Then the response status code should be 422
    And the "errors.notes" property should contain "Please add some notes about this token"
    And the "type" property should contain "validation_error"
    And the "Content-Type" header should be "application/problem+json"
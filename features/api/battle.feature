Feature: Battle
  In order to pit programmers against projects in a battle
  As an API Client
  I need to be able to start and view battles

  Background:
    Given the user "weaverryan" exists
    And "weaverryan" has an authentication token "ABC123"
    And I set the "Authorization" header to be "token ABC123"

  Scenario: Create a new battle
    Given there is a project called "wookie_dance"
    And there is a programmer called "GeekDev"
    And I have the payload:
      """
        {
          "programmerId": "%programmers.GeekDev.id%",
          "projectId": "%projects.wookie_dance.id%"
        }
      """
    When I request "POST /api/battles"
    Then the response status code should be 201
    And the "Location" header should exist
    And the "didProgrammerWin" property should exist

  Scenario: Create a new battle without a valid programmerId
    Given there is a project called "wookie_dance"
    And there is a programmer called "GeekDev"
    And I have the payload:
      """
        {
          "programmerId": "foobar",
          "projectId": "%projects.wookie_dance.id%"
        }
      """
    When I request "POST /api/battles"
    Then the response status code should be 422
    And the "Content-Type" header should be "application/problem+json"
    And the "type" property should contain "validation_error"
    And the "errors.programmerId" property should contain "Invalid or missing programmerId"

  Scenario: Create a new battle without a valid projectId
    Given there is a project called "wookie_dance"
    And there is a programmer called "GeekDev"
    And I have the payload:
      """
        {
          "programmerId": "%programmers.GeekDev.id%",
          "projectId": "foobar"
        }
      """
    When I request "POST /api/battles"
    Then the response status code should be 422
    And the "Content-Type" header should be "application/problem+json"
    And the "type" property should contain "validation_error"
    And the "errors.projectId" property should contain "Invalid or missing projectId"
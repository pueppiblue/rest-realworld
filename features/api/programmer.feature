Feature: Programmer
  In order to battle projects
  As an API client
  I need to be able to create programmers and power them up

  Background:
     # actually does create the user
     Given the user "weaverryan" exists

  Scenario: Create a programmer
    Given I have the payload:
      """
        {
          "nickname": "GeekDev",
          "avatarNumber": "2",
          "tagLine": "I am in for a test!"
        }
      """
    When I request "POST /api/programmers"
    Then the response status code should be 201
    And the "Location" header should be "/api/programmers/GeekDev"
    And the "nickname" property should equal "GeekDev"

  Scenario: GET one programmer
    Given the following programmers exist:
      | nickname    | avatarNumber  |
      | UnitTester  | 3             |
    When I request "GET /api/programmers/UnitTester"
    Then the response status code should be 200
    And the following properties should exist:
      """
      nickname
      avatarNumber
      powerLevel
      tagLine
      """
    And the "nickname" property should equal "UnitTester"

  Scenario: GET a collection of programmers
    Given the following programmers exist:
      | nickname      | avatarNumber  |
      | UnitTester    | 3             |
      | GeekDev       | 2             |
      | ApiBoss       | 1             |
      | FrontendDev   | 5             |
    When I request "GET /api/programmers"
    Then the response status code should be 200
    And the "programmers" property should be an array
    And the "programmers" property should contain 4 items

  Scenario: Use PUT to edit a programmer
    Given the following programmers exist:
      | nickname    | avatarNumber  | tagLine         |
      | UnitTester  | 3             | I like PHPUnit  |
    And I have the payload:
      """
        {
          "nickname": "UnitTester",
          "avatarNumber": "1",
          "tagLine": "But i love BEHAT!"
        }
      """
    When I request "PUT /api/programmers/UnitTester"
    Then the response status code should be 200
    And the "Location" header should be "/api/programmers/UnitTester"
    And the "tagLine" property should equal "But i love BEHAT!"
    And the "avatarNumber" property should equal "1"

Feature: Programmer
  In order to battle projects
  As an API client
  I need to be able to create programmers and power them up

  Background:
    # actually does create the user in the database
    Given the user "weaverryan" exists
    And the user "pueppiblue" exists
    And "weaverryan" has an authentication token "ABC123"
    And "pueppiblue" has an authentication token "XYZ789"
    And I set the "Authorization" header to be "token ABC123"

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

  Scenario: Validation error on CREATE without a nickname property on Request Entity
    Given I have the payload:
      """
        {
          "tagLine": "I am in for a test!",
          "avatarNumber": "2"
        }
      """
    When I request "POST /api/programmers"
    Then the response status code should be 422
    And the "Content-Type" header should be "application/problem+json"
    And the "type" property should contain "validation_error"
    And the "errors.nickname" property should exist
    And the "errors.avatarNumber" property should not exist

  Scenario: Invalid JSON sent in POST request
    Given I have the payload:
      """
        {
          "avatarNumber": "2 :" 80"
          "tagLine": "I am in for a test!"
        }
      """
    When I request "POST /api/programmers"
    Then the response status code should be 400
    And the "Content-Type" header should be "application/problem+json"
    And the "type" property should contain "invalid_body_format"

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
    And the "userId" property should not exist
    And the "_links.self.href" property should equal "/api/programmers/UnitTester"

  Scenario: GET non-existent programmer results in JSON 404 Response
    When I request "GET /api/programmers/non-existent-programmer"
    Then the response status code should be 404
    And the "Content-Type" header should be "application/problem+json"
    And the "type" property should equal "about:blank"
    And the "title" property should equal "Not Found"
    And the "detail" property should contain "not found in database"

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

  Scenario: Use PUT to update a programmer
    Given the following programmers exist:
      | nickname    | avatarNumber  | tagLine         |
      | UnitTester  | 3             | I like PHPUnit  |
    And I have the payload:
      """
        {
          "nickname": "FuncTester",
          "avatarNumber": "1",
          "tagLine": "But i love BEHAT!",
          "powerLevel": "4"
        }
      """
    When I request "PUT /api/programmers/UnitTester"
    Then the response status code should be 200
    And the "Location" header should be "/api/programmers/UnitTester"
    And the "tagLine" property should equal "But i love BEHAT!"
    And the "avatarNumber" property should equal "1"
    And the "nickname" property should equal "UnitTester"

  Scenario: Use PATCH to edit a programmer
    Given the following programmers exist:
      | nickname    | avatarNumber  | tagLine         |
      | UnitTester  | 3             | I like PHPUnit  |
    And I have the payload:
      """
        {
          "tagLine": "But i love BEHAT!"
        }
      """
    When I request "PATCH /api/programmers/UnitTester"
    Then the response status code should be 200
    And the "Location" header should be "/api/programmers/UnitTester"
    And the "tagLine" property should equal "But i love BEHAT!"
    And the "avatarNumber" property should equal "3"
    And the "nickname" property should equal "UnitTester"


  Scenario: DELETE a programmer
    Given the following programmers exist:
      | nickname    | avatarNumber  | tagLine         |
      | UnitTester  | 3             | I like PHPUnit  |
    When I request "DELETE /api/programmers/UnitTester"
    Then the response status code should be 204



#  Scenario: Throw an Error if not sending json on PUT
#    Given the following programmers exist:
#      | nickname      | avatarNumber  |
#      | UnitTester    | 3             |
#    And I have the payload:
#      """
#        {
#          "nickname": "UnitTester"
#          "avatarNumber" => "1"
#          "tagLine" = "But i love BEHAT!"
#        }
#      """
#
#    When I request "PUT /api/programmers/UnitTester"
#    Then the response status code should be 500

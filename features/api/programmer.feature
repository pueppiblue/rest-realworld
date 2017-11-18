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
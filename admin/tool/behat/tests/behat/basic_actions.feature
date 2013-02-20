@tool_behat
Feature: Page contents assertions
  In order to write good tests
  As a tests writer
  I need to check the page contents

  @javascript
  Scenario: Basic contents assertions
    Given I log in as "admin"
    And I am on homepage
    And I expand "Users" node
    And I follow "Groups"
    And I press "Create group"
    And I fill the moodle form with:
      | Group name | I'm the name |
      | Group description | I'm the description |
    And I press "Save changes"
    When I follow "Overview"
    And I wait until the page is ready
    And I wait "2" seconds
    And I hover ".region-content .generaltable td span"
    Then I should see "I'm the description"
    And I should see "Filter groups by"
    And I should not see "Filter groupssss by"
    And I should see "Group members" in the ".region-content table th.c1" element
    And I should not see "Group membersssss" in the ".region-content table th.c1" element
    And I follow "Groups"
    And the element "#groupeditform #showcreateorphangroupform" should be enabled
    And the element "#groupeditform #showeditgroupsettingsform" should be disabled

@core @core_grades @javascript
Feature: Verify Weighted Mean does not enable Extra Credits from the Grade Item Dialog
  In order to update grade items
  As a teacher
  I need to be able to edit the grade item and category settings

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "grade categories" exist:
      | fullname        | course | aggregation |
      | Cat simple      | C1     | 11          |
      | Cat weighted    | C1     | 10          |
    And the following "grade items" exist:
      | itemname  | course | category       | aggregationcoef | aggregationcoef2 | weightoverride |
      | Extra     | C1     | Cat simple     | 0               | 0                | 0              |
      | Weighted  | C1     | Cat weighted   | 2.5             | 0                | 0              |
    And I log in as "admin"
    And I change window size to "large"
    And I set the following administration settings values:
      | grade_aggregations_visible | Weighted mean of grades,Simple weighted mean of grades,Natural |

  Scenario: Test saving the grade item popup does not enable extra credits
    Given I am on the "Course 1" "grades > gradebook setup" page
    Then I set the following settings for grade item "Course 1" of type "course" on "setup" page:
      | Aggregation | 10 |
    Given I am on the "Course 1" "grades > Grader report > View" page
    And I turn editing mode on
    And I click on grade item menu "Extra" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Extra credit" matches value "0"
    And I click on "Show more..." "link" in the ".modal-dialog" "css_element"
    Then the field "Extra credit" matches value "0"
    And I press "Save"
    And I click on grade item menu "Extra" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Extra credit" matches value "0"
    And I click on "Save" "button" in the "Edit grade item" "dialogue"
    And I click on grade item menu "Extra" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Extra credit" matches value "0"
    And I click on "Cancel" "button" in the "Edit grade item" "dialogue"

  Scenario: Test saving the grade item popup does save extra credits
    Given I am on the "Course 1" "grades > gradebook setup" page
    Then I set the following settings for grade item "Course 1" of type "course" on "setup" page:
      | Aggregation | 10 |
    Given I am on the "Course 1" "grades > Grader report > View" page
    And I turn editing mode on
    And I click on grade item menu "Weighted" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Item weight" matches value "2.5"
    And I click on "Show more..." "link" in the ".modal-dialog" "css_element"
    Then the field "Item weight" matches value "2.5"
    And I press "Save"
    And I click on grade item menu "Weighted" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Item weight" matches value "2.5"
    And I click on "Save" "button" in the "Edit grade item" "dialogue"
    And I click on grade item menu "Weighted" of type "gradeitem" on "grader" page
    And I choose "Edit grade item" in the open action menu
    Then the field "Item weight" matches value "2.5"
    And I click on "Cancel" "button" in the "Edit grade item" "dialogue"

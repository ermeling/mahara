@javascript @core @core_administration
Feature: Create a site portfolio page with template blocks
In order to create a site portfolio page
As an admin
I can edit the site portfolio page and populate with template blocks
As a user
I can copy the site page and have blocks pre-populated with information

Background:
    Given the following "users" exist:
    | username | password  | email             | firstname | lastname | institution | authname | role   | studentid | town       | country | occupation |
    | UserA    | Kupuh1pa! | UserA@example.org | Angela    | User     | mahara      | internal | member | superA1   | Wellington | nz      | Plumber    |
    | UserB    | Kupuh1pa! | UserB@example.org | Bob       | User     | mahara      | internal | member | normalB2  | Oslo       | Norway  | Welder     |

Scenario: Add a text block into the site default portfolio page and create a new portfolio page (Bug 1488255)
    Given I log in as "admin" with password "Kupuh1pa!"
    And I choose "Pages and collections" in "Configure site" from administration menu
    And I follow "Add"
    And I click on "Page" in the dialog
    And I set the field "Page title" to "Site page with block templates" 
    And I press "Save"
    # Add a Profile information block
    When I follow "Add a new block" in the "blocktype sidebar" property
    And I press "Add"
    And I click on "Show more"
    And I click on "Show more"
    And I click on "Show more"
    And I click on "Profile information" in the "Content types" property
    And I set the following fields to these values:
    | First name     | 1 |
    | Last name      | 1 |
    | Student ID     | 1 |
    | Display name   | 1 |
    | Postal address | 1 |
    | Town           | 1 |
    | Country        | 1 |
    And I press "Save"
    And I should see "Postal address"
    And I follow "Share" in the "Toolbar buttons" property
    And I select "Registered users" from "General" in shared with select2 box
    And I expand "Advanced options" node
    And I enable the switch "Allow copying"
    And I press "Save"
    And I log out

    # Copy site portfolio page for UserA
    And I log in as "UserA" with password "Kupuh1pa!"
    And I choose "Pages and collections" in "Create" from main menu
    And I follow "Copy"
    And I press "Copy page"
    And I follow "Display page"
    Then I should see "Town: Wellington"
    And I log out

    # Copy site portfolio page for UserB
    And I log in as "UserB" with password "Kupuh1pa!"
    And I choose "Pages and collections" in "Create" from main menu
    And I follow "Copy"
    And I press "Copy page"
    And I follow "Display page"
    Then I should see "Town: Oslo"
    And I log out

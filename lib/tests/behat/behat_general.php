<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * General use steps definitions.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Cross component steps definitions.
 *
 * Basic web application definitions from MinkExtension and
 * BehatchExtension. Definitions modified according to our needs
 * when necessary and including only the ones we need to avoid
 * overlapping and confusion.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_general extends behat_base {

    /**
     * Opens Moodle homepage.
     *
     * @Given /^I am on homepage$/
     */
    public function i_am_on_homepage() {
        $this->getSession()->visit($this->locatePath('/'));
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^I follow "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     */
    public function click_link($link) {
        $link = $this->fixStepArgument($link);

        $linknode = $this->find_link($link);
        $linknode->click();
    }

    /**
     * Waits X seconds. Required after an action that requires data from an AJAX request.
     *
     * @Then /^I wait "(?P<seconds_number>\d+)" seconds$/
     * @param int $seconds
     */
    public function i_wait_seconds($seconds) {
        $this->getSession()->wait($seconds * 1000, false);
    }

    /**
     * Waits until the page is completely loaded. This step is auto-executed after every step.
     *
     * @Given /^I wait until the page is ready$/
     */
    public function wait_until_the_page_is_ready() {
        $this->getSession()->wait(self::TIMEOUT, '(document.readyState === "complete")');
    }

    /**
     * Mouse over a CSS element.
     *
     * @When /^I hover "(?P<element_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element
     */
    public function i_hover($element) {

        $exception = new ExpectationException(
            'The hovered element "' . $element . '" was not found anywhere in the page', $this->getSession()
        );

        $node = $this->find('css', $element, $exception);
        $node->mouseOver();
    }

    /**
     * Checks, that page contains specified text.
     *
     * @see Behat\MinkExtension\Context\MinkContext
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)"$/
     */
    public function assert_page_contains_text($text) {
        $this->assertSession()->pageTextContains($this->fixStepArgument($text));
    }

    /**
     * Checks, that page doesn't contain specified text.
     *
     * @see Behat\MinkExtension\Context\MinkContext
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)"$/
     */
    public function assert_page_not_contains_text($text) {
        $this->assertSession()->pageTextNotContains($this->fixStepArgument($text));
    }

    /**
     * Checks, that element with specified CSS contains specified text.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" element$/
     */
    public function assert_element_contains_text($text, $element) {
        $this->assertSession()->elementTextContains('css', $element, $this->fixStepArgument($text));
    }

    /**
     * Checks, that element with specified CSS doesn't contain specified text.
     *
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" element$/
     */
    public function assert_element_not_contains_text($text, $element) {
        $this->assertSession()->elementTextNotContains('css', $element, $this->fixStepArgument($text));
    }

    /**
     * Checks, that element with given CSS is disabled.
     *
     * @Then /^the element "(?P<element_string>(?:[^"]|\\")*)" should be disabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element
     */
    public function the_element_should_be_disabled($element) {

        $exception = new ExpectationException('There is no "' . $element . '" element', $this->getSession());
        $node = $this->find('css', $element, $exception);

        if (!$node->hasAttribute('disabled')) {
            throw new ExpectationException('The element "' . $element . '" is not disabled', $this->getSession());
        }
    }

    /**
     * Checks, that element with given CSS is enabled.
     *
     * @Then /^the element "(?P<element_string>(?:[^"]|\\")*)" should be enabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element
     */
    public function the_element_should_be_enabled($element) {

        $exception = new ExpectationException('There is no "' . $element . '" element', $this->getSession());
        $node = $this->find('css', $element, $exception);

        if ($node->hasAttribute('disabled')) {
            throw new ExpectationException('The element "' . $element . '" is not enabled', $this->getSession());
        }
    }

}

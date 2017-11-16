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
 * Dropbox Endpoint error.
 *
 * @since       Moodle 3.2
 * @package     repository_dropbox
 * @copyright   2017 Blackboard Inc
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace repository_dropbox;

defined('MOODLE_INTERNAL') || die();

/**
 * Dropbox Endpoint error.
 *
 * @package     repository_dropbox
 * @copyright   2017 Blackboard Inc
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class endpoint_exception extends dropbox_exception {
    /**
     * Constructor for endpoint_exception.
     */
    public function __construct($data) {
        if (is_object($data)) {
            // Show API based string if in the lang pack else error summary attribute.
            if (get_string_manager()->string_exists('error_' . $data->error->{'.tag'}, 'repository_dropbox')) {
                parent::__construct(get_string('endpointerror', 'repository_dropbox',
                                        get_string('error_' . $data->error->{'.tag'}, 'repository_dropbox')));
            } else {
                parent::__construct(get_string('endpointerror', 'repository_dropbox', $data->error_summary));
            }
        } else {
            parent::__construct(get_string('endpointerror', 'repository_dropbox', $data));
        }
    }
}

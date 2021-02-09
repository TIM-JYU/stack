<?php
// This file is part of STACK - http://stack.maths.ed.ac.uk/
//
// Stack is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stack is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.


require_once(__DIR__ . '/mathsoutputfilterbase.class.php');

/**
 * STACK maths output methods for using MathJax with the minimal API.
 *
 * @copyright  2017 The University of Edinburgh
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stack_maths_output_api extends stack_maths_output_filter_base {

    protected function initialise_delimiters() {
        $this->displaywrapstart = '';
        $this->displaywrapend = '';
        $this->displaystart = '\[';
        $this->displayend = '\]';
        $this->inlinestart = '\(';
        $this->inlineend = '\)';
    }
    protected function make_filter() {
        return new minimal_filter();
    }
}

/**
 * Passthrough filter for the minimal API
 */
class minimal_filter {
    public function filter($str) {
        return $str;
    }
}

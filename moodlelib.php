<?php
// This file is part of Stack - http://stack.maths.ed.ac.uk/
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

defined('MOODLE_INTERNAL') || die();

/* The purpose of this file is to define moodle-specific functions.
 * Replacements of these functions will be needed by other interfaces to STACK,
 * e.g. the API and ILIAS.
 */

require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/stack/mathsoutput/mathsoutput.class.php');

/**
 * Base class for all the types of exception we throw.
 */
class stack_exception extends moodle_exception {
    public function __construct($error) {
        parent::__construct('exceptionmessage', 'qtype_stack', '', $error);
    }
}

/**
 * Equivalent to get_string($key, 'qtype_stack', $a), but this method ensure that
 * any equations in the string are displayed properly.
 * @param string $key the string name.
 * @param mixed $a (optional) any values to interpolate into the string.
 * @return string the language string
 */
function stack_string($key, $a = null) {
    return stack_maths::process_lang_string(get_string($key, 'qtype_stack', $a));
}

/*
 * The API needs to do its own formatting of casstrings.
 */
function stack_maxima_format_casstring($str) {
    // Santise the output, E.g. '>' -> '&gt;'.
    $str = stack_string_sanitise($str);
    $str = str_replace('[[syntaxexamplehighlight]', '<span class="stacksyntaxexamplehighlight">', $str);
    $str = str_replace('[syntaxexamplehighlight]]', '</span>', $str);

    return html_writer::tag('span', $str, array('class' => 'stacksyntaxexample'));
}

/**
 * You need to call this method on the string you get from
 * $castext->get_display_castext() before you echo it. This ensures that equations
 * are displayed properly.
 * @param string $castext the result of calling $castext->get_display_castext().
 * @return string HTML ready to output.
 */
function stack_ouput_castext($castext) {
    return format_text(stack_maths::process_display_castext($castext),
            FORMAT_HTML, array('noclean' => true));
}

/**
 * Used by the questiontest*.php scripts, and deploy.php, to do some initialisation
 * that is needed on all of them.
 * @return array page context, selected seed (or null), and URL parameters.
 */
function qtype_stack_setup_question_test_page($question) {
    global $PAGE;

    $seed = optional_param('seed', null, PARAM_INT);
    $urlparams = array('questionid' => $question->id);
    if (!is_null($seed) && $question->has_random_variants()) {
        $urlparams['seed'] = $seed;
    }

    // Were we given a particular context to run the question in?
    // This affects things like filter settings, or forced theme or language.
    if ($cmid = optional_param('cmid', 0, PARAM_INT)) {
        $cm = get_coursemodule_from_id(false, $cmid);
        require_login($cm->course, false, $cm);
        $context = context_module::instance($cmid);
        $urlparams['cmid'] = $cmid;

    } else if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
        require_login($courseid);
        $context = context_course::instance($courseid);
        $urlparams['courseid'] = $courseid;

    } else {
        require_login();
        $context = $question->get_context();
        $PAGE->set_context($context);
        // Note that in the other cases, require_login will set the correct page context.
    }

    return array($context, $seed, $urlparams);
}


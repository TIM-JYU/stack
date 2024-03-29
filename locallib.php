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

/**
 * Private helper used by the next function.
 *
 * @return array search => replace strings.
 */
function get_stack_maxima_latex_replacements() {
    // This is an array language code => replacements array.
    static $replacements = [];

    $lang = current_language();
    if (!isset($replacements[$lang])) {
        $replacements[$lang] = [
                'QMCHAR' => '?',
                '!LEFTSQ!' => '\left[',
                '!LEFTR!' => '\left(',
                '!RIGHTSQ!' => '\right]',
                '!RIGHTR!' => '\right)',
                '!ANDOR!' => stack_string('equiv_ANDOR'),
                '!SAMEROOTS!' => stack_string('equiv_SAMEROOTS'),
                '!MISSINGVAR!' => stack_string('equiv_MISSINGVAR'),
                '!ASSUMEPOSVARS!' => stack_string('equiv_ASSUMEPOSVARS'),
                '!ASSUMEPOSREALVARS!' => stack_string('equiv_ASSUMEPOSREALVARS'),
                '!LET!' => stack_string('equiv_LET'),
                '!AND!' => stack_string('equiv_AND'),
                '!OR!' => stack_string('equiv_OR'),
                '!NOT!' => stack_string('equiv_NOT'),
                '!NAND!' => stack_string('equiv_NAND'),
                '!NOR!' => stack_string('equiv_NOR'),
                '!XOR!' => stack_string('equiv_XOR'),
                '!XNOR!' => stack_string('equiv_XNOR'),
                '!IMPLIES!' => stack_string('equiv_IMPLIES'),
                '!BOOLTRUE!' => stack_string('true'),
                '!BOOLFALSE!' => stack_string('false'),
        ];
    }
    return $replacements[$lang];
}

/**
 * This function tidies up LaTeX from Maxima.
 * @param string $rawfeedback
 * @return string
 */
function stack_maxima_latex_tidy($latex) {
    $replacements = get_stack_maxima_latex_replacements();
    $latex = str_replace(array_keys($replacements), array_values($replacements), $latex);

    // Also previously some spaces have been eliminated and line changes dropped.
    // Apparently returning verbatim LaTeX was not a thing.
    $latex = str_replace("\n ", '', $latex);
    $latex = str_replace("\n", '', $latex);
    // Just don't want to use regexp.
    $latex = str_replace('    ', ' ', $latex);
    $latex = str_replace('   ', ' ', $latex);
    $latex = str_replace('  ', ' ', $latex);

    return $latex;
}

/**
 * This function takes a feedback string from Maxima and unpacks and translates it.
 * @param string $rawfeedback
 * @return string
 */
function stack_maxima_translate($rawfeedback) {

    if (strpos($rawfeedback, 'stack_trans') === false) {
        return trim(stack_maxima_latex_tidy($rawfeedback));
    } else {
        $rawfeedback = str_replace('[[', '', $rawfeedback);
        $rawfeedback = str_replace(']]', '', $rawfeedback);
        $rawfeedback = str_replace("\n", '', $rawfeedback);
        $rawfeedback = str_replace('\n', '', $rawfeedback);
        $rawfeedback = str_replace('!quot!', '"', $rawfeedback);

        $translated = array();
        preg_match_all('/stack_trans\(.*?\);/', $rawfeedback, $matches);
        $feedback = $matches[0];
        foreach ($feedback as $fb) {
            $fb = substr($fb, 12, -2);
            if (strstr($fb, "' , \"") === false) {
                // We only have a feedback tag, with no optional arguments.
                $translated[] = trim(stack_string(substr($fb, 1, -1)));
            } else {
                // We have a feedback tag and some optional arguments.
                $tag = substr($fb, 1, strpos($fb, "' , \"") - 1);
                $arg = substr($fb, strpos($fb, "' , \"") + 5, -2);
                $args = explode('"  , "', $arg);

                $a = array();
                for ($i = 0; $i < count($args); $i++) {
                    $a["m{$i}"] = $args[$i];
                }
                $translated[] = trim(stack_string($tag, $a));
            }
        }

        return stack_maxima_latex_tidy(implode(' ', $translated));
    }
}

function stack_string_sanitise($str) {
    // Students may not input strings containing specific LaTeX
    // i.e. no math-modes due to us being unable to decide if
    // it is safe.
    $str = str_replace('\\[', '\\&#8203;[', $str);
    $str = str_replace('\\]', '\\&#8203;]', $str);
    $str = str_replace('\\(', '\\&#8203;(', $str);
    $str = str_replace('\\)', '\\&#8203;)', $str);
    $str = str_replace('$$', '$&#8203;$', $str);
    // Also any script tags need to be disabled.
    $str = str_ireplace('<script', '&lt;&#8203;script', $str);
    $str = str_ireplace('</script>', '&lt;&#8203;/script&gt;', $str);
    $str = str_ireplace('<iframe', '&lt;&#8203;iframe', $str);
    $str = str_ireplace('</iframe>', '&lt;&#8203;/iframe&gt;', $str);
    $str = str_ireplace('<style', '&lt;&#8203;style', $str);
    $str = str_ireplace('</style>', '&lt;&#8203;/style&gt;', $str);
    return $str;
}


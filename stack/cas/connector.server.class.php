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

global $PLOT_MIMES;
$PLOT_MIMES = array(
    'png' => 'image/png',
    'svg' => 'image/svg+xml'
);

/**
 * Connection to Maxima running in a tomcat-server using the MaximaPool-servlet.
 * This version handles transfer of the plots generated on possibly remote servlet.
 * For details of this see https://github.com/maths/stack_util_maximapool/
 *
 * @copyright  2012 The University of Birmingham
 * @copyright  2012 Aalto University - Matti Harjula
 * @copyright  2014 Loughborough University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stack_cas_connection_server extends stack_cas_connection_base {

    protected function guess_maxima_command($path) {
        return 'http://localhost:8080/MaximaPool/MaximaPool';
    }

    protected function call_maxima($command) {
        global $PLOT_MIMES;
        global $CFG;
        $err = '';

        $starttime = microtime(true);

        $request = curl_init($this->command);

        $postdata = 'input=' . urlencode($command) .
                '&timeout=' . ($this->timeout * 1000) .
                '&ploturlbase=!ploturl!' .
                '&version=' . stack_connection_helper::get_required_stackmaxima_version();

        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        if (!empty($this->serveruserpass)) {
            curl_setopt($request, CURLOPT_USERPWD, $this->serveruserpass);
        }

        $ret = curl_exec($request);

        $timedout = false;

        // The servlet will return 416 if the evaluation hits the timelimit.
        if (curl_getinfo($request, CURLINFO_HTTP_CODE) != '200') {
            if (curl_getinfo($request, CURLINFO_HTTP_CODE) != '416') {
                throw new Exception('stack_cas_connection: MaximaPool error: '.curl_getinfo($request, CURLINFO_HTTP_CODE));
            } else {
                $timedout = true;
            }
        }

        $plots = array();

        // Did we get files?
        if (strpos(curl_getinfo($request, CURLINFO_CONTENT_TYPE), "text/plain") === false) {
            // We have to save the zip file on local disk before opening...
            // how come there is no core library solution to this!?
            // create temp file, save zip there.
            $ziptemp = $CFG->dataroot . "/stack/tmp/";
            $ziptemp = tempnam($ziptemp, "zip");
            $fp = fopen($ziptemp, "w");
            fwrite($fp, $ret);
            fclose($fp);
            $zip = zip_open($ziptemp);
            $entry = zip_read($zip);
            // Read the entrys of the archive.
            while ($entry !== false) {
                // This one contains the output from maxima.
                if (zip_entry_name($entry) == 'OUTPUT') {
                    zip_entry_open($zip, $entry);
                    $ret = zip_entry_read($entry, zip_entry_filesize($entry));
                    zip_entry_close($entry);
                } else {
                    $entry_name = zip_entry_name($entry);
                    //$filename = $CFG->dataroot . "/stack/plots/" . zip_entry_name($entry);
                    zip_entry_open($zip, $entry);
                    //$fp = fopen($filename, 'w');
                    $data = "";
                    $buffy = zip_entry_read($entry, 2048);
                    while ($buffy != '') {
                        $data = $data . $buffy;
                        $buffy = zip_entry_read($entry, 2048);
                    }
                    $fname = pathinfo($entry_name, PATHINFO_BASENAME);
                    $plots[$fname] = base64_encode($data);
                    zip_entry_close($entry);
                }

                $entry = zip_read($zip);
            }
            zip_close($zip);
            // Clean up.
            unlink($ziptemp);
        }

        if (!empty($plots)) {
            foreach ($plots as $name => $data) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $mime = $PLOT_MIMES[$ext];
                $plot_url = '!ploturl!'.$name;
                $img_data = 'data:'.$mime.';base64,'.$data;
                $ret = str_replace($plot_url, $img_data, $ret);
            }
        }

        curl_close($request);

        $now = microtime(true);

        $this->debug->log('Timings', "Start: {$starttime}, End: {$now}, Taken = ".($now - $starttime));

        // Add sufficient closing ]'s to allow something to be un-parsed from the CAS.
        // WARNING: the string 'The CAS timed out' is used by the cache to serach for a timout occurance.
        if ($timedout) {
            $ret .= ' The CAS timed out. ] ] ] ]';
        }

        return $ret;
    }
}

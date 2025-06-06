<?php

defined('MOODLE_INTERNAL') || die();

if (!isset($CFG)) {
    require_once(dirname(__DIR__, 2) . '/config.php');
}
require_once($CFG->libdir . '/gradelib.php');

class filter_echo_url extends moodle_text_filter {
    public function filter($text, array $options = []) {
        global $USER, $COURSE, $PAGE;
    
        if (!is_string($text)) {
            return $text;
        }
    
        // Match either: full <a href="https://talk.echoai.ge?..."> or raw bare link
        $pattern = '#<a[^>]+href="(https://talk\.echoai\.ge\?[^"]+)"[^>]*>[^<]*</a>|(?<!href=")(https://talk\.echoai\.ge\?[^"\s<]+)#';
    
        return preg_replace_callback($pattern, function ($match) use ($USER, $COURSE, $PAGE) {
            $baseurl = $match[1] ?? $match[2];
    
            $userid = intval($USER->id ?? 0);
            $email = (!empty($USER->email)) ? $USER->email : 'moodle_' . $userid;
            $courseid = intval($COURSE->id ?? 0);
            $cmid = intval($PAGE->cm->id ?? 0);
            $title = $PAGE->cm->name ?? 'Untitled';
            $gradename = "Echo: {$title} ({$cmid})";
    
            // Ensure grade item exists
            $this->ensure_grade_item_exists($courseid, $gradename, $cmid);
    
            // Extend Echo URL with Moodle-specific context
            $url = new moodle_url($baseurl, [
                'c' => $email,
                'in' => 'moodle',
                'user' => $userid,
                'section' => $courseid,
                'grade' => $gradename,
                'api' => (new moodle_url('/filter/echo_url/api.php'))->out(false)
            ]);
    
            // Replace with styled Echo button
            return html_writer::tag('a', 'Start Test', [
                'href' => $url->out(false),
                'target' => '_blank',
                'class' => 'btn btn-primary',
                'style' => '
                    display: inline-block;
                    padding: 10px 24px;
                    background: linear-gradient(to right, #00B2FF, #C084FC);
                    color: white;
                    text-decoration: none;
                    border: none;
                    border-radius: 8px;
                    font-weight: 500;
                    font-size: 14px;
                    text-align: center;
                '
            ]);
        }, $text);
    }
    

    private function ensure_grade_item_exists($courseid, $gradename, $cmid) {
        global $DB;
    
        $idnumber = 'echo_' . $cmid;
    
        // Check if grade item exists by idnumber (reliable and unique)
        $exists = $DB->record_exists('grade_items', [
            'courseid' => $courseid,
            'idnumber' => $idnumber,
            'itemtype' => 'manual'
        ]);
    
        if ($exists) {
            return;
        }
    
        // ❗ Use direct object creation to avoid unintended updates
        $grade_item = new grade_item([
            'courseid' => $courseid,
            'itemtype' => 'manual',
            'itemname' => $gradename,
            'idnumber' => $idnumber,
            'gradetype' => GRADE_TYPE_TEXT
        ]);
    
        $grade_item->insert();
        $grade_item->update();
    }
}

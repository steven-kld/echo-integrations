<?php

defined('MOODLE_INTERNAL') || die();

if (!isset($CFG)) {
    require_once(dirname(__DIR__, 2) . '/config.php');
}
require_once($CFG->libdir . '/gradelib.php');

class filter_echo_url extends moodle_text_filter {
    public function filter($text, array $options = []) {
        global $USER, $COURSE, $PAGE;

        if (!is_string($text) || strpos($text, '{echo_url}') === false) {
            return $text;
        }

        $userid = intval($USER->id ?? 0);
        $courseid = intval($COURSE->id ?? 0);
        $cmid = intval($PAGE->cm->id ?? 0);
        $title = $PAGE->cm->name ?? 'Untitled';
        $gradename = "Echo: {$title} ({$cmid})";

        // Auto-create grade item if it doesn't exist
        $this->ensure_grade_item_exists($courseid, $gradename, $cmid);

        // Build Echo URL with full context
        $url = new moodle_url('https://talk.getecho.io', [
            'in' => 'moodle',
            'user' => $userid,
            'section' => $courseid,
            'grade' => $gradename
        ]);

        // Render gradient button
        $button = html_writer::tag('a', 'Open Echo Interview', [
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

        return str_replace('{echo_url}', $button, $text);
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

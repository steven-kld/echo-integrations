<?php
class filter_echo_url extends moodle_text_filter {
    public function filter($text, array $options = []) {
        global $USER, $COURSE;

        if (!is_string($text) || strpos($text, '{echo_url}') === false) {
            return $text;
        }

        $userid = isset($USER->id) ? intval($USER->id) : 0;
        $courseid = isset($COURSE->id) ? intval($COURSE->id) : 0;

        $url = new moodle_url('https://talk.getecho.io', [
            'in' => 'moodle',
            'user' => $USER->id,
            'section' => $COURSE->id
        ]);

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
}